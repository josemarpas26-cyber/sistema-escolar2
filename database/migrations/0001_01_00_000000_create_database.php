<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateDatabase extends Migration
{
    public function up()
    {
        // Comando SQL para criar o banco de dados
        DB::statement('CREATE DATABASE IF NOT EXISTS sistema_nota');
    }


}
