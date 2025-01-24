<?php namespace Expando\LocoGraphQLPackage;

use League\OAuth2\Client\Provider\GenericProvider;

class OwnGenericProvider extends GenericProvider
{
    protected function getAllowedClientOptions(array $options): array
    {
        return ['timeout', 'proxy', 'verify'];
    }

}
