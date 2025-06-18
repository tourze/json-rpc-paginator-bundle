<?php

namespace Tourze\JsonRPCPaginatorBundle\Procedure;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Service\Attribute\Required;
use Tourze\JsonRPC\Core\Attribute\MethodParam;

/**
 * 如果分页页数传入太多的话，可能会带来分页上的性能问题，一般来说我们都不太可能拉取那么多的数据，所以我这里直接限制了最大1000页
 * 如果有传入 lastId，则优先基于这个来查询
 *
 * @see https://github.com/KnpLabs/knp-components/blob/master/docs/pager/intro.md#custom-data-repository-pagination
 * @see https://blog.51cto.com/u_12592884/2697559
 */
trait PaginatorTrait
{
    #[MethodParam('每页条数')]
    #[Assert\Range(min: 1, max: 2000)]
    public int $pageSize = 10;

    #[MethodParam('当前页数')]
    #[Assert\Range(min: 1, max: 1000)]
    public int $currentPage = 1;

    #[MethodParam('上一次拉取时，最后一条数据的主键ID')]
    public ?int $lastId = null;

    #[Required]
    public PaginatorInterface $paginator;

    /**
     * 当需要覆盖默认的PageSize时，自己处理即可
     */
    protected function getDefaultPageSize(int $prevValue): int
    {
        return $prevValue;
    }

    /**
     * 拉取列表
     *
     * @param callable|null $counter 计算总数
     */
    protected function fetchList(Query|QueryBuilder $queryBuilder, callable $formatter, ?callable $counter = null): array
    {
        $pageSize = $this->getDefaultPageSize($this->pageSize);

        //        if ($this->lastId !== null) {
        //            $count = function () use ($query) {
        //                // 参考 \Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\QuerySubscriber
        //            };
        //            $items = function ($offset, $limit) use ($query) {
        //                $query->setMaxResults($pageSize);
        //            };
        //            $target = new CallbackPagination($count, $items);
        //            $pagination = $this->paginator->paginate($target, $this->currentPage, $pageSize);
        //        } else {
        //            $pagination = $this->paginator->paginate($query, $this->currentPage, $pageSize);
        //        }
        // TODO 等入参统一使用了 QueryBuilder，我们就改造上面注释的分页逻辑
        // TODO 对于没有 lastId 但是明确可以使用延迟关联的场景，我们考虑使用延迟关联来查询
        $pagination = $this->paginator->paginate($queryBuilder, $this->currentPage, $pageSize);

        $result = [
            'list' => [],
            'pagination' => [
                'current' => $pagination->getCurrentPageNumber(),
                'pageSize' => $pagination->getItemNumberPerPage(),
                'total' => $counter !== null ? $counter($queryBuilder, $pagination) : $pagination->getTotalItemCount(),
                'hasMore' => true,
            ],
        ];
        foreach ($pagination as $item) {
            $tmp = $formatter($item);
            // 如果返回的是一个迭代器，那么我们就要一个个从里面拿数据咯
            if ($tmp instanceof \Traversable) {
                foreach ($tmp as $_tmp) {
                    $result['list'][] = $_tmp;
                }
            } else {
                if (!is_array($tmp)) {
                    continue;
                }
                $result['list'][] = $tmp;
            }
        }

        if (($pagination->getCurrentPageNumber() * $pagination->getItemNumberPerPage()) >= $pagination->getTotalItemCount()) {
            $result['pagination']['hasMore'] = false;
        }

        return $result;
    }

    /**
     * 空列表
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
