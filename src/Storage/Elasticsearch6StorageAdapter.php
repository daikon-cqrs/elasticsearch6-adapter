<?php
/**
 * This file is part of the daikon-cqrs/elasticsearch6-adapter project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Daikon\Elasticsearch6\Storage;

use Daikon\Dbal\Exception\DbalException;
use Daikon\Elasticsearch6\Connector\Elasticsearch6Connector;
use Daikon\ReadModel\Projection\ProjectionInterface;
use Daikon\ReadModel\Projection\ProjectionMap;
use Daikon\ReadModel\Query\QueryInterface;
use Daikon\ReadModel\Storage\SearchAdapterInterface;
use Daikon\ReadModel\Storage\StorageAdapterInterface;
use Elasticsearch\Common\Exceptions\Missing404Exception;

final class Elasticsearch6StorageAdapter implements StorageAdapterInterface, SearchAdapterInterface
{
    /** @var Elasticsearch6Connector */
    private $connector;

    /** @var array */
    private $settings;

    public function __construct(Elasticsearch6Connector $connector, array $settings = [])
    {
        $this->connector = $connector;
        $this->settings = $settings;
    }

    public function read(string $identifier): ?ProjectionInterface
    {
        try {
            $document = $this->connector->getConnection()->get(
                array_merge($this->settings['read'] ?? [], [
                    'index' => $this->getIndex(),
                    'type' => $this->settings['type'],
                    'id' => $identifier
                ])
            );
        } catch (Missing404Exception $error) {
            return null;
        }

        $projectionClass = $document['_source']['@type'];
        return $projectionClass::fromNative($document['_source']);
    }

    public function write(string $identifier, array $data): bool
    {
        $document = array_merge($this->settings['write'] ?? [], [
            'index' => $this->getIndex(),
            'type' => $this->settings['type'],
            'id' => $identifier,
            'body' => $data
        ]);

        $this->connector->getConnection()->index($document);

        return true;
    }

    public function delete(string $identifier): bool
    {
        throw new DbalException('Not implemented');
    }

    public function search(QueryInterface $query, int $from = null, int $size = null): ProjectionMap
    {
        $query = array_merge($this->settings['search'] ?? [], [
            'index' => $this->getIndex(),
            'type' => $this->settings['type'],
            'from' => $from,
            'size' => $size,
            'body' => $query->toNative()
        ]);

        $results = $this->connector->getConnection()->search($query);

        $projections = [];
        foreach ($results['hits']['hits'] as $document) {
            $projectionClass = $document['_source']['@type'];
            $projections[$document['_id']] = $projectionClass::fromNative($document['_source']);
        }

        return new ProjectionMap($projections);
    }

    private function getIndex(): string
    {
        return $this->settings['index'] ?? $this->connector->getSettings()['index'];
    }
}
