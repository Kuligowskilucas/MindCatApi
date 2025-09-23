<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Testing\Assert;

class UsersTest extends TestCase
{
    use RefreshDatabase;



    //example
    /** @test */
    public function a_user_can_be_created()
    {
        //arrange
        $userData = [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
        ];
        
        //act
        $user = User::factory()->create($userData);

        //assert
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
        ]);
    }


    /** @test */
    public function user_can_register()
    {
        //arrange
        $userData = [
            'name' => 'Matheus Henrique de Oliveira',
            'email' => 'matheushro.dev@gmail.com',
            'password' => 'minha_senha_nova_cat_api_18937219832',
        ];
        
        //act
        $response = $this->postJson('/api/register', $userData);

        //assert
        $this->assertDatabaseHas('users', [
            'name' => 'Matheus Henrique de Oliveira',
            'email' => 'matheushro.dev@gmail.com',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Usuário registrado com sucesso',
            'user' => [
                'name' => 'Matheus Henrique de Oliveira',
                'email' => 'matheushro.dev@gmail.com',
            ],
            'token' => $response->json('token'),
        ]);
    }

    /** @test */
    public function user_cant_register_without_name()
    {
        //arrange
        $userData = [
            'email' => 'matheushro.dev@gmail.com',
            'password' => 'minha_senha_nova_cat_api_18937219832',
        ];
        
        //act
        $response = $this->postJson('/api/register', $userData);

        //assert
        $this->assertDatabaseMissing('users', [
            'email' => 'matheushro.dev@gmail.com',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The name field is required.',
        ]);
    }

    /** @test */
    public function user_cant_register_with_email_already_exists()
    {
        //arrange
        $userData = [
            'name' => 'Matheus Henrique de Oliveira',
            'email' => 'matheushro.dev@gmail.com',
            'password' => 'minha_senha_nova_cat_api_18937219832',
        ];

        $userRegistered = User::factory()->create([
            'name' => 'Matheus Henrique de Oliveira 1',
            'email' => 'matheushro.dev@gmail.com',
        ]);

        //act
        $response = $this->postJson('/api/register', $userData);

        //assert
        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The email has already been taken.',
        ]);
    }

}