# JsonRPC Paginator Bundle

[English](README.md) | [中文](README.zh-CN.md)

This bundle provides a simple JsonRPC pagination implementation that integrates with Symfony projects. It depends on KnpPaginatorBundle to handle the underlying pagination functionality.

## Installation

```bash
composer require tourze/json-rpc-paginator-bundle
```

## Features

- Supports Doctrine ORM query pagination
- Supports query result formatting
- Supports custom count callbacks
- Provides empty list structure generation

## Basic Usage

Use the PaginatorTrait in your JsonRPC procedure class:

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
    #[MethodParam('keyword')]
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
                // Other fields...
            ];
        });
    }
    
    // Optional: Override default page size
    protected function getDefaultPageSize(int $prevValue): int
    {
        return 20; // Custom default page size
    }
}
```

## Return Data Structure

```json
{
  "list": [
    { "id": 1, "name": "User 1", "email": "user1@example.com" },
    { "id": 2, "name": "User 2", "email": "user2@example.com" }
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
