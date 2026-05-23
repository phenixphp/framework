<?php

declare(strict_types=1);

namespace Phenix\Http\Client\Concerns;

use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Socket\Certificate;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use SensitiveParameter;

trait HasTls
{
    public function withTlsContext(ClientTlsContext $tlsContext): self
    {
        $connectContext = (new ConnectContext())->withTlsContext($tlsContext);
        $connectionFactory = new DefaultConnectionFactory(connectContext: $connectContext);

        $this->builder = $this->builder->usingPool(new UnlimitedConnectionPool($connectionFactory));
        $this->client = $this->builder->build();

        return $this;
    }

    public function withCertificate(
        string $certificate,
        string|null $key = null,
        string|null $ca = null,
        #[SensitiveParameter]
        string|null $passphrase = null,
        string $peerName = ''
    ): self {
        $tlsContext = (new ClientTlsContext($peerName))
            ->withCertificate(new Certificate($certificate, $key, $passphrase));

        if ($ca !== null) {
            $tlsContext = $tlsContext->withCaFile($ca);
        }

        return $this->withTlsContext($tlsContext);
    }
}
