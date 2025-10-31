<?php

declare(strict_types=1);

namespace Tourze\JsonRPCPaginatorBundle\Procedure;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Service\Attribute\Required;
use Tourze\JsonRPC\Core\Attribute\MethodParam;

/**
 * 如果分页页数传入太多的话，可能会带来分页上的性能问题，一般来说我们都不太可能拉取那么多的数据，所以我这里直接限制了最大1000页
 * 如果有传入 lastId，则优先基于这个来查询.
 *
 * @see https://github.com/KnpLabs/knp-components/blob/master/docs/pager/intro.md#custom-data-repository-pagination
 * @see https://blog.51cto.com/u_12592884/2697559
 *
 * @phpstan-ignore trait.unused
 */
trait PaginatorTrait
{
    #[MethodParam(description: '每页条数')]
    #[Assert\Range(min: 1, max: 2000)]
    public int $pageSize = 10;

    #[MethodParam(description: '当前页数')]
    #[Assert\Range(min: 1, max: 1000)]
    public int $currentPage = 1;

    #[MethodParam(description: '上一次拉取时，最后一条数据的主键ID')]
    public ?int $lastId = null;

    #[Required]
    public PaginatorInterface $paginator;

    /**
     * 当需要覆盖默认的PageSize时，自己处理即可.
     */
    protected function getDefaultPageSize(int $prevValue): int
    {
        return $prevValue;
    }

    /**
     * 拉取列表.
     *
     * @param callable|null $counter 计算总数
     *
     * @return array<string, mixed>
     */
    protected function fetchList(Query|QueryBuilder $queryBuilder, callable $formatter, ?callable $counter = null): array
    {
        $pageSize = $this->getDefaultPageSize($this->pageSize);
        $pagination = $this->paginator->paginate($queryBuilder, $this->currentPage, $pageSize);

        return [
            'list' => $this->formatPaginationItems($pagination, $formatter),
            'pagination' => $this->buildPaginationInfo($pagination, $queryBuilder, $counter),
        ];
    }

    /**
     * 格式化分页项目.
     *
     * @param iterable<mixed> $pagination
     *
     * @return array<mixed>
     */
    private function formatPaginationItems(iterable $pagination, callable $formatter): array
    {
        $list = [];

        foreach ($pagination as $item) {
            $this->addFormattedItem($list, $formatter($item));
        }

        return $list;
    }

    /**
     * 添加格式化后的项目到列表.
     *
     * @param array<mixed> $list
     */
    private function addFormattedItem(array &$list, mixed $formattedItem): void
    {
        if ($formattedItem instanceof \Traversable) {
            foreach ($formattedItem as $item) {
                $list[] = $item;
            }

            return;
        }

        if (is_array($formattedItem)) {
            $list[] = $formattedItem;
        }
    }

    /**
     * 构建分页信息.
     *
     * @param PaginationInterface<int, mixed> $pagination
     *
     * @return array<string, mixed>
     */
    private function buildPaginationInfo(object $pagination, Query|QueryBuilder $queryBuilder, ?callable $counter): array
    {
        $currentPage = $pagination->getCurrentPageNumber();
        $itemsPerPage = $pagination->getItemNumberPerPage();
        $totalItems = null !== $counter ? $counter($queryBuilder, $pagination) : $pagination->getTotalItemCount();

        return [
            'current' => $currentPage,
            'pageSize' => $itemsPerPage,
            'total' => $totalItems,
            'hasMore' => ($currentPage * $itemsPerPage) < $totalItems,
        ];
    }

    /**
     * 空列表.
     *
     * @return array<string, mixed>
     */
    protected function emptyList(): array
    {
        return [
            'list' => [],
            'pagination' => [
                'current' => $this->currentPage,
                'pageSize' => $this->pageSize,
                'total' => 0,
                'hasMore' => false,
            ],
        ];
    }
}
