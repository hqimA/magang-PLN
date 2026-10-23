<?php

namespace App\Services;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use RuntimeException;
use Throwable;

class RemoteImageDownloader
{
    private const MAX_BYTES = 10 * 1024 * 1024;

    private const MAX_REDIRECTS = 3;

    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    private ClientInterface $client;

    /**
     * @var Closure(string): array<int, string>
     */
    private Closure $resolveHost;

    /**
     * @param  Closure(string): array<int, string>|null  $resolveHost
     */
    public function __construct(?ClientInterface $client = null, ?Closure $resolveHost = null)
    {
        $this->client = $client ?? new Client;
        $this->resolveHost = $resolveHost ?? static function (string $host): array {
            if (filter_var($host, FILTER_VALIDATE_IP)) {
                return [$host];
            }

            $records = dns_get_record($host, DNS_A | DNS_AAAA) ?: [];

            return array_values(array_filter(array_map(
                static fn (array $record): ?string => $record['ip'] ?? $record['ipv6'] ?? null,
                $records,
            )));
        };
    }

    public function download(string $url): string
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'vehicle-image-');

        if ($temporaryPath === false) {
            throw new RuntimeException('File sementara untuk foto kendaraan tidak dapat dibuat.');
        }

        try {
            $currentUrl = $url;

            for ($redirect = 0; $redirect <= self::MAX_REDIRECTS; $redirect++) {
                [$host, $address] = $this->validatedPublicHost($currentUrl);
                $response = $this->client->request('GET', $currentUrl, [
                    'allow_redirects' => false,
                    'connect_timeout' => 5,
                    'http_errors' => false,
                    'sink' => $temporaryPath,
                    'timeout' => 15,
                    'on_headers' => static function ($response): void {
                        $contentLength = $response->getHeaderLine('Content-Length');

                        if ($contentLength !== '' && (int) $contentLength > self::MAX_BYTES) {
                            throw new RuntimeException('Ukuran foto melebihi batas 10 MB.');
                        }
                    },
                    'progress' => static function (
                        float $downloadTotal,
                        float $downloadedBytes,
                    ): void {
                        if ($downloadTotal > self::MAX_BYTES || $downloadedBytes > self::MAX_BYTES) {
                            throw new RuntimeException('Ukuran foto melebihi batas 10 MB.');
                        }
                    },
                    ...$this->curlResolveOption($host, $address),
                ]);

                if (in_array($response->getStatusCode(), [301, 302, 303, 307, 308], true)) {
                    $location = $response->getHeaderLine('Location');

                    if ($location === '' || $redirect === self::MAX_REDIRECTS) {
                        throw new RuntimeException('Tautan foto memiliki pengalihan yang tidak valid atau terlalu banyak.');
                    }

                    $currentUrl = (string) UriResolver::resolve(new Uri($currentUrl), new Uri($location));

                    continue;
                }

                if ($response->getStatusCode() !== 200) {
                    throw new RuntimeException('Tautan foto tidak dapat diunduh dari server sumber.');
                }

                $this->validateDownloadedImage($temporaryPath);

                return $temporaryPath;
            }

            throw new RuntimeException('Tautan foto memiliki pengalihan yang terlalu banyak.');
        } catch (Throwable $exception) {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }

            throw $exception;
        }
    }

    /**
     * @return array{string, string}
     */
    private function validatedPublicHost(string $url): array
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('Foto Kendaraan harus berupa tautan HTTPS langsung ke gambar.');
        }

        $parts = parse_url($url);
        $host = is_array($parts) ? strtolower(rtrim(trim($parts['host'] ?? '', '[]'), '.')) : '';

        if (strtolower($parts['scheme'] ?? '') !== 'https'
            || $host === ''
            || isset($parts['user'])
            || isset($parts['pass'])
            || (isset($parts['port']) && $parts['port'] !== 443)) {
            throw new RuntimeException('Foto Kendaraan harus berupa tautan HTTPS publik tanpa kredensial.');
        }

        $addresses = ($this->resolveHost)($host);

        if ($addresses === []) {
            throw new RuntimeException('Server tautan foto tidak dapat ditemukan.');
        }

        foreach ($addresses as $address) {
            if (filter_var(
                $address,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            ) === false) {
                throw new RuntimeException('Tautan foto tidak boleh mengarah ke jaringan lokal atau alamat IP khusus.');
            }
        }

        return [$host, $addresses[0]];
    }

    /**
     * @return array{curl?: array<int, array<int, string>>}
     */
    private function curlResolveOption(string $host, string $address): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [];
        }

        $resolvedAddress = str_contains($address, ':') ? "[{$address}]" : $address;

        return [
            'curl' => [
                CURLOPT_RESOLVE => ["{$host}:443:{$resolvedAddress}"],
            ],
        ];
    }

    private function validateDownloadedImage(string $path): void
    {
        $size = filesize($path);

        if ($size === false || $size === 0 || $size > self::MAX_BYTES) {
            throw new RuntimeException('Foto Kendaraan kosong atau melebihi batas 10 MB.');
        }

        $image = @getimagesize($path);

        if (! is_array($image)
            || ! in_array($image['mime'] ?? null, self::ALLOWED_MIME_TYPES, true)) {
            throw new RuntimeException('Tautan tidak berisi gambar JPG, PNG, atau WebP yang valid.');
        }

        if (($image[0] * $image[1]) > 20_000_000) {
            throw new RuntimeException('Resolusi foto kendaraan tidak boleh melebihi 20 megapiksel.');
        }
    }
}
