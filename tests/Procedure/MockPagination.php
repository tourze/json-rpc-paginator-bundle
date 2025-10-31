<?php

declare(strict_types=1);

namespace Tourze\JsonRPCPaginatorBundle\Tests\Procedure;

use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * 模拟分页结果的类.
 *
 * @internal
 *
 * @implements PaginationInterface<mixed, mixed>
 * @implements \IteratorAggregate<mixed, mixed>
 */
class MockPagination implements PaginationInterface, \IteratorAggregate
{
    /** @var array<mixed> */
    private array $items;

    private int $currentPage;

    private int $itemsPerPage;

    private int $totalCount;

    /** @var array<string, mixed> */
    private array $options = [];

    /** @var array<string, mixed> */
    private array $customParameters = [];

    /**
     * @param array<mixed> $items
     */
    public function __construct(array $items, int $currentPage, int $itemsPerPage, int $totalCount)
    {
        $this->items = $items;
        $this->currentPage = $currentPage;
        $this->itemsPerPage = $itemsPerPage;
        $this->totalCount = $totalCount;
    }

    /**
     * @return array<mixed>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getCurrentPageNumber(): int
    {
        return $this->currentPage;
    }

    public function getItemNumberPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function getTotalItemCount(): int
    {
        return $this->totalCount;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPaginationData(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCustomParameters(): array
    {
        return $this->customParameters;
    }

    public function setCustomParameters(array $parameters): void
    {
        $this->customParameters = $parameters;
    }

    public function getCustomParameter(string $name): mixed
    {
        return $this->customParameters[$name] ?? null;
    }

    public function getPaginatorOption(string $name): mixed
    {
        return $this->options[$name] ?? null;
    }

    public function setCurrentPageNumber(int $pageNumber): void
    {
        $this->currentPage = $pageNumber;
    }

    public function setItemNumberPerPage(int $numItemsPerPage): void
    {
        $this->itemsPerPage = $numItemsPerPage;
    }

    public function setItems(array|\Traversable $items): void
    {
        if ($items instanceof \Traversable) {
            $this->items = iterator_to_array($items);
        } else {
            $this->items = $items;
        }
    }

    public function setPaginatorOptions(array $options): void
    {
        $this->options = $options;
    }

    public function setTotalItemCount(int $numTotal): void
    {
        $this->totalCount = $numTotal;
    }

    public function offsetExists($offset): bool
    {
        if (!is_int($offset) && !is_string($offset)) {
            return false;
        }

        return isset($this->items[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        if (!is_int($offset) && !is_string($offset)) {
            return null;
        }

        return $this->items[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if (null === $offset) {
            $this->items[] = $value;
        } elseif (is_int($offset) || is_string($offset)) {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        if (is_int($offset) || is_string($offset)) {
            unset($this->items[$offset]);
        }
    }
}
