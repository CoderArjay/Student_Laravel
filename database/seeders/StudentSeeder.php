<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Student;
use Faker\Factory as Faker;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $faker = Faker::create('en_PH'); // Create a Faker instance for Filipino locale

        for ($i = 0; $i < 10; $i++) {
            Student::create([
                'LRN' => $faker->unique()->numberBetween(1000000000, 9999999999), // Generate a unique LRN
                'lname' => $faker->lastName,
                'fname' => $faker->firstName,
                'mname' => $faker->firstName, // Middle name can be first name for simplicity
                'suffix' => $faker->randomElement(['', 'Jr.', 'Sr.', 'III']), // Random suffix
                'bdate' => $faker->date('Y-m-d', '-18 years'), // Birthdate (18 years ago)
                'bplace' => $faker->city, // Birthplace (random city)
                'gender' => $faker->randomElement(['Male', 'Female']), // Random gender
                'religion' => $faker->randomElement(['Catholic', 'Protestant', 'Adventist', 'INC']), // Random religion
                'address' => $faker->address,
                'contact_no' => $this->generate_ph_mobile_number(), // Generate Philippine mobile number
                'email' => $faker->unique()->safeEmail,
                // Use a default password or hash it if needed
                'password' => bcrypt('password'), // Default password (hashed)
            ]);
        }
    }

    private function generate_ph_mobile_number()
    {
        // Common mobile prefixes in the Philippines
        $prefixes = ['0915', '0916', '0917', '0918', '0919', 
                     '0920', '0921', '0922', '0923', '0924'];
        
        // Select a random prefix from the array
        $prefix = $prefixes[array_rand($prefixes)];
        
        // Generate the remaining 7 digits
        return $prefix . rand(1000000, 9999999);
    }
}
