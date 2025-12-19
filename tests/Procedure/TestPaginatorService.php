<?php

declare(strict_types=1);

namespace Tourze\JsonRPCPaginatorBundle\Tests\Procedure;

use Tourze\JsonRPCPaginatorBundle\Param\PaginatorParamInterface;
use Tourze\JsonRPCPaginatorBundle\Procedure\PaginatorTrait;

/**
 * 用于测试的 PaginatorTrait 服务类.
 *
 * 在集成测试中，接受 mixed 类型以便传入数组进行测试，
 * 因为真实的 PaginatorInterface 支持数组分页。
 *
 * @internal
 */
class TestPaginatorService
{
    use PaginatorTrait;

    /**
     * 公开 protected 方法以便测试.
     */
    public function publicGetDefaultPageSize(int $prevValue): int
    {
        return $this->getDefaultPageSize($prevValue);
    }

    /**
     * 公开 protected 方法以便测试.
     *
     * 在集成测试中，使用数组作为数据源传给 PaginatorInterface，
     * 因为真实的 Knp Paginator 原生支持数组分页。
     *
     * @param array<mixed> $target 数据源数组
     * @return array<string, mixed>
     */
    public function publicFetchListFromArray(
        array $target,
        callable $formatter,
        ?callable $counter = null,
        ?PaginatorParamInterface $param = null,
    ): array {
        // 为了向后兼容，如果未传入 param，尝试使用旧的属性（如果存在）
        $pageSize = $this->resolvePaginatorPageSize($param);
        $currentPage = $this->resolvePaginatorCurrentPage($param);

        $pageSize = $this->getDefaultPageSize($pageSize);
        $pagination = $this->paginator->paginate($target, $currentPage, $pageSize);

        return [
            'list' => $this->formatPaginationItemsPublic($pagination, $formatter),
            'pagination' => $this->buildPaginationInfoPublic($pagination, $target, $counter, $param),
        ];
    }

    /**
     * 格式化分页项目（公开版本用于测试）
     *
     * @param iterable<mixed> $pagination
     * @return array<mixed>
     */
    private function formatPaginationItemsPublic(iterable $pagination, callable $formatter): array
    {
        $list = [];
        foreach ($pagination as $item) {
            $formattedItem = $formatter($item);
            if ($formattedItem instanceof \Traversable) {
                foreach ($formattedItem as $subItem) {
                    $list[] = $subItem;
                }
            } elseif (is_array($formattedItem)) {
                $list[] = $formattedItem;
            }
        }
        return $list;
    }

    /**
     * 构建分页信息（公开版本用于测试）
     *
     * @param array<mixed> $target
     * @return array<string, mixed>
     */
    private function buildPaginationInfoPublic(
        object $pagination,
        array $target,
        ?callable $counter,
        ?PaginatorParamInterface $param = null,
    ): array {
        /** @var \Knp\Component\Pager\Pagination\PaginationInterface<int, mixed> $pagination */
        $currentPage = $pagination->getCurrentPageNumber();
        $itemsPerPage = $pagination->getItemNumberPerPage();
        $totalItems = null !== $counter ? $counter($target, $pagination) : $pagination->getTotalItemCount();

        return [
            'current' => $currentPage,
            'pageSize' => $itemsPerPage,
            'total' => $totalItems,
            'hasMore' => ($currentPage * $itemsPerPage) < $totalItems,
        ];
    }

    /**
     * 解析分页大小（公开访问）
     */
    private function resolvePaginatorPageSize(?PaginatorParamInterface $param): int
    {
        if (null !== $param) {
            if (method_exists($param, 'getPageSize')) {
                return $param->getPageSize();
            }
            if (property_exists($param, 'pageSize')) {
                return $param->pageSize;
            }
        }
        return 10;
    }

    /**
     * 解析当前页码（公开访问）
     */
    private function resolvePaginatorCurrentPage(?PaginatorParamInterface $param): int
    {
        if (null !== $param) {
            if (method_exists($param, 'getCurrentPage')) {
                return $param->getCurrentPage();
            }
            if (property_exists($param, 'currentPage')) {
                return $param->currentPage;
            }
        }
        return 1;
    }

    /**
     * 公开 protected 方法以便测试.
     *
     * @return array<string, mixed>
     */
    public function publicEmptyList(?PaginatorParamInterface $param = null): array
    {
        return $this->emptyList($param);
    }
}
