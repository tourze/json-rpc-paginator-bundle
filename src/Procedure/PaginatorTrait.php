<?php

declare(strict_types=1);

namespace Tourze\JsonRPCPaginatorBundle\Procedure;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Tourze\JsonRPCPaginatorBundle\Param\PaginatorParamInterface;

/**
 * 分页处理 Trait
 *
 * 在新架构下，分页参数（pageSize, currentPage, lastId）应在 Param 类中定义。
 * 该 Trait 仅提供分页处理方法，需要传入实现 PaginatorParamInterface 的 Param 对象。
 *
 * @see https://github.com/KnpLabs/knp-components/blob/master/docs/pager/intro.md#custom-data-repository-pagination
 * @see https://blog.51cto.com/u_12592884/2697559
 */
trait PaginatorTrait
{
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
    protected function fetchList(
        Query|QueryBuilder $queryBuilder,
        callable $formatter,
        ?callable $counter = null,
        ?PaginatorParamInterface $param = null,
    ): array {
        // 为了向后兼容，如果未传入 param，尝试使用旧的属性（如果存在）
        $pageSize = $this->resolvePaginatorPageSize($param);
        $currentPage = $this->resolvePaginatorCurrentPage($param);

        $pageSize = $this->getDefaultPageSize($pageSize);
        $pagination = $this->paginator->paginate($queryBuilder, $currentPage, $pageSize);

        return [
            'list' => $this->formatPaginationItems($pagination, $formatter),
            'pagination' => $this->buildPaginationInfo($pagination, $queryBuilder, $counter, $param),
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
    private function buildPaginationInfo(
        object $pagination,
        Query|QueryBuilder $queryBuilder,
        ?callable $counter,
        ?PaginatorParamInterface $param = null,
    ): array {
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
    protected function emptyList(?PaginatorParamInterface $param = null): array
    {
        $currentPage = $this->resolvePaginatorCurrentPage($param);
        $pageSize = $this->resolvePaginatorPageSize($param);

        return [
            'list' => [],
            'pagination' => [
                'current' => $currentPage,
                'pageSize' => $pageSize,
                'total' => 0,
                'hasMore' => false,
            ],
        ];
    }

    /**
     * 解析分页大小
     *
     * 支持两种方式：
     * 1. 调用 $param->getPageSize() 方法
     * 2. 访问 $param->pageSize 属性
     */
    private function resolvePaginatorPageSize(?PaginatorParamInterface $param): int
    {
        if (null !== $param) {
            // 优先使用 getter 方法（推荐方式）
            if (method_exists($param, 'getPageSize')) {
                return $param->getPageSize();
            }
            // 回退到属性访问（向后兼容）
            if (property_exists($param, 'pageSize')) {
                return $param->pageSize;
            }
        }

        // 向后兼容：检查 Trait 使用者的旧属性
        if (property_exists($this, 'pageSize')) {
            return $this->pageSize;
        }

        return 10;
    }

    /**
     * 解析当前页码
     *
     * 支持两种方式：
     * 1. 调用 $param->getCurrentPage() 方法
     * 2. 访问 $param->currentPage 属性
     */
    private function resolvePaginatorCurrentPage(?PaginatorParamInterface $param): int
    {
        if (null !== $param) {
            // 优先使用 getter 方法（推荐方式）
            if (method_exists($param, 'getCurrentPage')) {
                return $param->getCurrentPage();
            }
            // 回退到属性访问（向后兼容）
            if (property_exists($param, 'currentPage')) {
                return $param->currentPage;
            }
        }

        // 向后兼容：检查 Trait 使用者的旧属性
        if (property_exists($this, 'currentPage')) {
            return $this->currentPage;
        }

        return 1;
    }
}
