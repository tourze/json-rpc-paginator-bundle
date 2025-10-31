# JsonRPC Paginator Bundle

![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?branch=master)
![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo/master)

[English](README.md) | [中文](README.zh-CN.md)

This bundle provides a simple JsonRPC pagination implementation that integrates with Symfony projects. It depends on KnpPaginatorBundle to handle the underlying pagination functionality.

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Features](#features)
- [Basic Usage](#basic-usage)
- [Return Data Structure](#return-data-structure)
- [License](#license)

## Installation

```bash
composer require tourze/json-rpc-paginator-bundle
```

## Quick Start

1. **Install the bundle**:
   ```bash
   composer require tourze/json-rpc-paginator-bundle
   ```

2. **Add the bundle to your kernel** (if not using Symfony Flex):
   ```php
   // config/bundles.php
   return [
       // ...
       Tourze\JsonRPCPaginatorBundle\JsonRPCPaginatorBundle::class => ['all' => true],
   ];
   ```

3. **Use the PaginatorTrait in your JsonRPC procedure**:
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

4. **Call your JsonRPC method**:
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

## Advanced Usage

### Custom Counter Callback

If you need custom counting logic (e.g., for performance optimization), you can provide a custom counter:

```php
return $this->fetchList(
    $queryBuilder,
    function ($item) {
        return ['id' => $item->getId(), 'name' => $item->getName()];
    },
    function ($queryBuilder, $pagination) {
        // Custom count logic
        return $this->customCountLogic($queryBuilder);
    }
);
```

### Handling Traversable Results

The formatter can return traversable results for one-to-many relationships:

```php
return $this->fetchList($queryBuilder, function ($user) {
    // Returns multiple items for each user
    foreach ($user->getRoles() as $role) {
        yield [
            'userId' => $user->getId(),
            'roleId' => $role->getId(),
            'roleName' => $role->getName(),
        ];
    }
});
```

### Empty List Handling

When no query is needed, you can return an empty list structure:

```php
if ($someCondition) {
    return $this->emptyList();
}
```

## Configuration

The PaginatorTrait provides several configuration options through class properties:

- `$pageSize` (default: 10): Items per page, range: 1-2000
- `$currentPage` (default: 1): Current page number, range: 1-1000
- `$lastId` (optional): Last item ID for cursor-based pagination (future feature)

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Requirements

- PHP 8.2 or higher
- Symfony 7.3 or higher
- Run tests: `./vendor/bin/phpunit packages/json-rpc-paginator-bundle/tests`
- Run static analysis: `php -d memory_limit=2G ./vendor/bin/phpstan analyse packages/json-rpc-paginator-bundle`

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
