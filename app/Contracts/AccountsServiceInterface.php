<?php

namespace App\Contracts;

interface AccountsServiceInterface
{
    public function summary(): array;
    public function ledger(string $year): array;
    public function staffList(): array;
    public function nextVoucher(string $prefix): string;
}
