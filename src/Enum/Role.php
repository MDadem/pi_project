<?php

//src/Enum/Role.php
namespace App\Enum;

enum Role: string
{
case Admin = 'ROLE_ADMIN';
case Student = 'ROLE_STUDENT';

}