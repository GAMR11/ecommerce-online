<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            [
                'name'=>'Gustavo Morales',
                'email'=>'gamr21@outlook.es',
                'password'=>'$2y$10$qO4Mo4Ey.PS2aHwsttOOoeiNwsJJVFKKVT.0gm15QPSJpXmxMXJki' /** Morales98 */
            ]
        );


        User::firstOrCreate(
            [
                'name'=>'Jaime Morales',
                'email'=>'comercialgusmor@gmail.com',
                'password'=>'$2y$10$0smTGDvT1TT18l0mYyTGkuxMMqkOogIsU0Fjh1sOwQxGdk9gXi5tq' /** Gustavo123_ */
            ]
        );

        User::firstOrCreate(
            [
                'name'=>'Melanie Lemarie',
                'email'=>'melanielemarie@gmail.com',
                'password'=>'$2y$10$PzGqZCEvx59Kz7vMBcrJQ.XTwxfgOsBUBjXYOYZ8XusVV3Svw78.W' /** Melanie1234*/
            ]
        );
    }
}
