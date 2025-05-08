# JsonRPC分页实现

该包提供了一个简单的JsonRPC分页实现，可与Symfony项目集成。它依赖于KnpPaginatorBundle来处理底层分页功能。

## 安装

```bash
composer require tourze/json-rpc-paginator-bundle
```

## 功能

- 支持基于Doctrine ORM的查询分页
- 支持查询结果格式化
- 支持自定义计数回调
- 提供空列表结构生成

## 基本用法

在你的JsonRPC过程类中使用PaginatorTrait：

```php
<?php

namespace App\JsonRPC;

use Doctrine\ORM\QueryBuilder;
use Tourze\JsonRPCPaginatorBundle\Procedure\PaginatorTrait;
use Tourze\JsonRPC\Core\Attribute\JsonRPCMethod;
use Tourze\JsonRPC\Core\Attribute\MethodParam;

class UserProcedure
{
    use PaginatorTrait;
    
    #[JsonRPCMethod('user.list')]
    #[MethodParam('查询关键字')]
    public string $keyword = '';
    
    public function execute(): array
    {
        $qb = $this->createQueryBuilder()
            ->select('u')
            ->from('App:User', 'u');
            
        if ($this->keyword) {
            $qb->andWhere('u.name LIKE :keyword')
               ->setParameter('keyword', '%' . $this->keyword . '%');
        }
        
        return $this->fetchList($qb, function ($user) {
            return [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                // 其他字段...
            ];
        });
    }
    
    // 可选：覆盖默认页大小
    protected function getDefaultPageSize(int $prevValue): int
    {
        return 20; // 自定义默认页大小
    }
}
```

## 返回的数据结构

```json
{
  "list": [
    { "id": 1, "name": "用户1", "email": "user1@example.com" },
    { "id": 2, "name": "用户2", "email": "user2@example.com" }
  ],
  "pagination": {
    "current": 1,
    "pageSize": 20,
    "total": 42,
    "hasMore": true
  }
}
```

## License

MIT 