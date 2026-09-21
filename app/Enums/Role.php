<?php

namespace App\Enums;

enum Role: string
{
    case Patient = 'patient';
    case Pro = 'pro';
    case Admin = 'admin';
}
