<?php

namespace App\Imports;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
//view the errors
class UsersImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new User([
            'name' => $row['name'],
            'email' => $row['email'],
            'password' => bcrypt($row['password']),
        ]);
    }
}