<?php

declare(strict_types=1);

namespace Tourze\JsonRPCPaginatorBundle\Param;

use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

/**
 * 分页参数接口
 *
 * 实现此接口的类需要提供以下参数（通过公共属性或 getter 方法）：
 * - pageSize: 每页条数，默认 10，范围 1-2000
 * - currentPage: 当前页数，默认 1，范围 1-1000
 * - lastId: 上一次拉取时最后一条数据的主键 ID，可选参数
 *
 * 支持两种实现方式：
 * 1. 使用公共只读属性（推荐，适用于简单场景）
 * 2. 实现 getPageSize、getCurrentPage、getLastId 方法（推荐用于封装逻辑）
 */
interface PaginatorParamInterface extends RpcParamInterface
{
    // 标记接口：Trait 会通过反射检查属性或方法的存在性
}
