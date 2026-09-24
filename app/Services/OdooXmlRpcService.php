<?php

namespace App\Services;

use PhpXmlRpc\Client;
use PhpXmlRpc\Encoder;
use PhpXmlRpc\Request as XmlRpcRequest;
use PhpXmlRpc\Value;
use RuntimeException;

class OdooXmlRpcService
{
    private string $url;
    private string $db;
    private int $uid;
    private string $password;
    private Client $objectClient;
    private Encoder $encoder;

    public function __construct(?string $url = null, ?string $db = null, ?string $username = null, ?string $password = null)
    {
        $this->url = rtrim($url ?? (string) config('services.odoo.url'), '/');
        $this->db = $db ?? (string) config('services.odoo.db');
        $username = $username ?? (string) config('services.odoo.username');
        $this->password = $password ?? (string) config('services.odoo.password');

        if ($this->url === '' || $this->db === '' || $username === '') {
            throw new RuntimeException('Konfigurasi Odoo belum lengkap. Isi ODOO_URL, ODOO_DB, dan ODOO_USERNAME pada file .env.');
        }

        $this->encoder = new Encoder();
        $this->uid = $this->authenticate($username);
        $this->objectClient = $this->createClient('/xmlrpc/2/object');
    }

    public function uid(): int
    {
        return $this->uid;
    }

    /**
     * @param  array<int, mixed>  $fields
     * @param  array<int, mixed>  $domain
     * @param  array<string, mixed>  $context
     * @return array<int, array<string, mixed>>
     */
    public function searchRead(string $model, array $fields, ?int $limit = 50, array $domain = [], array $context = []): array
    {
        $kwargs = ['fields' => $fields];

        if ($limit !== null) {
            $kwargs['limit'] = $limit;
        }

        if ($context !== []) {
            $kwargs['context'] = $context;
        }

        $result = $this->executeKw($model, 'search_read', [$domain], $kwargs);

        return is_array($result) ? $result : [];
    }

    /**
     * @param  array<int, mixed>  $domain
     * @param  array<string, mixed>  $context
     */
    public function searchCount(string $model, array $domain = [], array $context = []): int
    {
        $kwargs = $context !== [] ? ['context' => $context] : [];

        return (int) $this->executeKw($model, 'search_count', [$domain], $kwargs);
    }

    /**
     * @param  array<int, mixed>  $args
     * @param  array<string, mixed>  $kwargs
     */
    public function executeKw(string $model, string $method, array $args = [], array $kwargs = []): mixed
    {
        $params = [
            new Value($this->db, 'string'),
            new Value($this->uid, 'int'),
            new Value($this->password, 'string'),
            new Value($model, 'string'),
            new Value($method, 'string'),
            $this->encoder->encode($args),
        ];

        if ($kwargs !== []) {
            $params[] = $this->encoder->encode($kwargs);
        }

        $response = $this->objectClient->send(new XmlRpcRequest('execute_kw', $params));

        if (!$response) {
            throw new RuntimeException('Tidak ada respons dari server Odoo.');
        }

        if ($response->faultCode()) {
            throw new RuntimeException('Odoo error: ' . $response->faultString());
        }

        return $this->encoder->decode($response->value());
    }

    private function authenticate(string $username): int
    {
        $client = $this->createClient('/xmlrpc/2/common');

        $response = $client->send(new XmlRpcRequest('authenticate', [
            new Value($this->db, 'string'),
            new Value($username, 'string'),
            new Value($this->password, 'string'),
            new Value([], 'array'),
        ]));

        if (!$response) {
            throw new RuntimeException('Tidak ada respons dari server Odoo.');
        }

        if ($response->faultCode()) {
            throw new RuntimeException('Odoo error: ' . $response->faultString());
        }

        $uid = (int) $response->value()->scalarVal();

        if ($uid <= 0) {
            throw new RuntimeException('Autentikasi Odoo gagal. Periksa database, username, dan password.');
        }

        return $uid;
    }

    private function createClient(string $endpoint): Client
    {
        $client = new Client($this->url . $endpoint);
        $verifySsl = (bool) config('services.odoo.verify_ssl', true);
        $options = [
            Client::OPT_TIMEOUT => (int) config('services.odoo.timeout', 30),
            Client::OPT_VERIFY_PEER => $verifySsl,
            Client::OPT_VERIFY_HOST => $verifySsl ? 2 : 0,
        ];
        $caCert = config('services.odoo.ca_cert');

        if (is_string($caCert) && $caCert !== '') {
            $options[Client::OPT_CA_CERT] = $caCert;
        }

        return $client->setOptions($options);
    }
}
