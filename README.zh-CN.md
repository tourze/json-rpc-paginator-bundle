# JsonRPC分页实现

![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?branch=master)
![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo/master)

[English](README.md) | [中文](README.zh-CN.md)

该包提供了一个简单的JsonRPC分页实现，可与Symfony项目集成。它依赖于KnpPaginatorBundle来处理底层分页功能。

## 目录

- [安装](#安装)
- [快速开始](#快速开始)
- [功能](#功能)
- [基本用法](#基本用法)
- [返回的数据结构](#返回的数据结构)
- [许可证](#许可证)

## 安装

```bash
composer require tourze/json-rpc-paginator-bundle
```

## 快速开始

1. **安装包**:
   ```bash
   composer require tourze/json-rpc-paginator-bundle
   ```

2. **添加包到内核** (如果不使用 Symfony Flex):
   ```php
   // config/bundles.php
   return [
       // ...
       Tourze\JsonRPCPaginatorBundle\JsonRPCPaginatorBundle::class => ['all' => true],
   ];
   ```

3. **在你的 JsonRPC 过程中使用 PaginatorTrait**:
   ```php
   <?php
   
   namespace App\JsonRPC;
   
   use Tourze\JsonRPCPaginatorBundle\Procedure\PaginatorTrait;
   
   class UserProcedure
   {
       use PaginatorTrait;
       
       public function execute(): array
       {
           $qb = $this->createQueryBuilder()
               ->select('u')
               ->from('App:User', 'u');
           
           return $this->fetchList($qb, function ($user) {
               return ['id' => $user->getId(), 'name' => $user->getName()];
           });
       }
   }
   ```

4. **调用你的 JsonRPC 方法**:
   ```json
   {
       "jsonrpc": "2.0",
       "method": "user.list",
       "params": {
           "currentPage": 1,
           "pageSize": 20
       },
       "id": 1
   }
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

## 高级用法

### 自定义计数回调

如果您需要自定义计数逻辑（例如，为了性能优化），可以提供自定义计数器：

```php
return $this->fetchList(
    $queryBuilder,
    function ($item) {
        return ['id' => $item->getId(), 'name' => $item->getName()];
    },
    function ($queryBuilder, $pagination) {
        // 自定义计数逻辑
        return $this->customCountLogic($queryBuilder);
    }
);
```

### 处理可遍历结果

格式化器可以为一对多关系返回可遍历的结果：

```php
return $this->fetchList($queryBuilder, function ($user) {
    // 为每个用户返回多个项目
    foreach ($user->getRoles() as $role) {
        yield [
            'userId' => $user->getId(),
            'roleId' => $role->getId(),
            'roleName' => $role->getName(),
        ];
    }
});
```

### 空列表处理

当不需要查询时，可以返回空列表结构：

```php
if ($someCondition) {
    return $this->emptyList();
}
```

## 配置

PaginatorTrait 通过类属性提供了几个配置选项：

- `$pageSize`（默认：10）：每页项目数，范围：1-2000
- `$currentPage`（默认：1）：当前页码，范围：1-1000
- `$lastId`（可选）：基于游标分页的最后一个项目ID（未来功能）

## 贡献指南

欢迎贡献！请遵循以下准则：

1. Fork 仓库
2. 创建功能分支（`git checkout -b feature/amazing-feature`）
3. 提交更改（`git commit -m 'Add some amazing feature'`）
4. 推送到分支（`git push origin feature/amazing-feature`）
5. 打开 Pull Request

### 开发要求

- PHP 8.2 或更高版本
- Symfony 7.3 或更高版本
- 运行测试：`./vendor/bin/phpunit packages/json-rpc-paginator-bundle/tests`
- 运行静态分析：`php -d memory_limit=2G ./vendor/bin/phpstan analyse packages/json-rpc-paginator-bundle`

## 许可证

MIT 许可证。详情请参阅 [许可证文件](LICENSE)。 