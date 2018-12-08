<?php
/**
 * This file is part of the daikon-cqrs/elasticsearch6-adapter project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Daikon\Elasticsearch6\Query;

use Daikon\ReadModel\Query\QueryInterface;

final class Elasticsearch6Query implements QueryInterface
{
    /** @var array */
    private $query;

    /** @param array $query */
    public static function fromNative($query): QueryInterface
    {
        return new self($query);
    }

    public function toNative(): array
    {
        return $this->query;
    }

    private function __construct(array $query = [])
    {
        $this->query = $query;
    }
}
