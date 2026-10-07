<?php

declare(strict_types=1);

namespace TronZap\Tests\Support;

use RuntimeException;

final class TestCertificate
{
    public string $caFile;

    public string $certFile;

    public string $keyFile;

    private static ?self $instance = null;

    private function __construct(string $dir)
    {
        $this->caFile = "$dir/ca.pem";
        $this->certFile = "$dir/cert.pem";
        $this->keyFile = "$dir/key.pem";
    }

    public static function get(): self
    {
        if (self::$instance === null) {
            self::$instance = self::generate();
        }
        return self::$instance;
    }

    private static function generate(): self
    {
        $dir = sys_get_temp_dir() . '/tronzap-tls-' . bin2hex(random_bytes(4));
        mkdir($dir);
        register_shutdown_function(static function () use ($dir): void {
            array_map('unlink', glob("$dir/*") ?: []);
            rmdir($dir);
        });
        $config = "$dir/openssl.cnf";
        // openssl_csr_sign takes extensions only from a config file, and Windows has no default one.
        file_put_contents($config, implode("\n", [
            '[req]',
            'distinguished_name = dn',
            '[dn]',
            '[ca]',
            'basicConstraints = critical, CA:TRUE',
            'keyUsage = critical, keyCertSign',
            '[leaf]',
            'basicConstraints = critical, CA:FALSE',
            'keyUsage = critical, digitalSignature, keyEncipherment',
            'extendedKeyUsage = serverAuth',
            'subjectAltName = DNS:localhost',
            '',
        ]));
        $options = ['config' => $config, 'digest_alg' => 'sha256', 'private_key_bits' => 2048];

        $caKey = openssl_pkey_new($options);
        $leafKey = openssl_pkey_new($options);
        if ($caKey === false || $leafKey === false) {
            throw new RuntimeException('Cannot create a test key: ' . openssl_error_string());
        }
        $caKeyRef = $caKey;
        $leafKeyRef = $leafKey;
        $caCsr = openssl_csr_new(['commonName' => 'TronZap SDK Test CA'], $caKeyRef, $options);
        $leafCsr = openssl_csr_new(['commonName' => 'localhost'], $leafKeyRef, $options);
        if (is_bool($caCsr) || is_bool($leafCsr)) {
            throw new RuntimeException('Cannot create a test CSR: ' . openssl_error_string());
        }
        $ca = openssl_csr_sign($caCsr, null, $caKey, 1, $options + ['x509_extensions' => 'ca'], 1);
        if ($ca === false) {
            throw new RuntimeException('Cannot sign the test CA: ' . openssl_error_string());
        }
        $leaf = openssl_csr_sign($leafCsr, $ca, $caKey, 1, $options + ['x509_extensions' => 'leaf'], 2);
        if ($leaf === false) {
            throw new RuntimeException('Cannot sign the test certificate: ' . openssl_error_string());
        }

        $certificate = new self($dir);
        openssl_x509_export_to_file($ca, $certificate->caFile);
        openssl_x509_export_to_file($leaf, $certificate->certFile);
        openssl_pkey_export_to_file($leafKey, $certificate->keyFile, null, $options);
        return $certificate;
    }
}
