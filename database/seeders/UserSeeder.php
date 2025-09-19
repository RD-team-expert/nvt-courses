<?php
// database/seeders/UserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserLevel;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $levels = UserLevel::all()->keyBy('code');
        $departments = Department::all()->keyBy('department_code');

        // Create main admin/executive users first
        User::create([
            'name' => 'Sam',
            'email' => 'sam@company.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'user_level_id' => $levels['L4']->id,
            'status' => 'active',
            'employee_code' => 'EMP001',
        ]);

        User::create([
            'name' => 'Moe',
            'email' => 'moe@company.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'user_level_id' => $levels['L4']->id,
            'status' => 'active',
            'employee_code' => 'EMP002',
        ]);

        // L4 Directors
        User::create([
            'name' => 'Julie',
            'email' => 'julie@company.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'user_level_id' => $levels['L4']->id,
            'department_id' => $departments['LOG']->id,
            'status' => 'active',
            'employee_code' => 'EMP003',
        ]);

        User::create([
            'name' => 'Emma',
            'email' => 'emma@company.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'user_level_id' => $levels['L4']->id,
            'department_id' => $departments['PIZZA']->id,
            'status' => 'active',
            'employee_code' => 'EMP004',
        ]);

        // L3 Senior Managers / Department Heads
        $l3Users = [
            ['name' => 'Batool', 'email' => 'batool@company.com', 'dept' => 'RE', 'code' => 'EMP005'],
            ['name' => 'George', 'email' => 'george@company.com', 'dept' => 'RD', 'code' => 'EMP006'],
            ['name' => 'David', 'email' => 'david@company.com', 'dept' => 'MKT', 'code' => 'EMP007'],
            ['name' => 'Majd', 'email' => 'majd@company.com', 'dept' => 'FIN', 'code' => 'EMP008'],
            ['name' => 'Nola', 'email' => 'nola@company.com', 'dept' => 'FIN', 'code' => 'EMP009'],
            ['name' => 'Harvey', 'email' => 'harvey@company.com', 'dept' => 'LOG', 'code' => 'EMP010'],
            ['name' => 'Ryan', 'email' => 'ryan@company.com', 'dept' => 'LOG', 'code' => 'EMP011'],
            ['name' => 'Leo', 'email' => 'leo@company.com', 'dept' => 'PIZZAOPS', 'code' => 'EMP012'],
            ['name' => 'Joseph', 'email' => 'joseph@company.com', 'dept' => 'PIZZAOPS', 'code' => 'EMP013'],
            ['name' => 'Estefan', 'email' => 'estefan@company.com', 'dept' => 'PIZZAOPS', 'code' => 'EMP014'],
            ['name' => 'Jimmy', 'email' => 'jimmy@company.com', 'dept' => 'PIZZAHIRE', 'code' => 'EMP015'],
            ['name' => 'Samantha', 'email' => 'samantha@company.com', 'dept' => 'GROWTH', 'code' => 'EMP016'],
            ['name' => 'Lucas', 'email' => 'lucas@company.com', 'dept' => 'PIZZAPM', 'code' => 'EMP017'],
        ];

        foreach ($l3Users as $userData) {
            User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
                'role' => 'manager',
                'user_level_id' => $levels['L3']->id,
                'department_id' => $departments[$userData['dept']]->id,
                'status' => 'active',
                'employee_code' => $userData['code'],
            ]);
        }

        // L2 Direct Managers
        $l2Users = [
            // Real Estate Department Managers
            ['name' => 'Hannah', 'email' => 'hannah@company.com', 'dept' => 'BUILD', 'code' => 'EMP018'],
            ['name' => 'Bia', 'email' => 'bia@company.com', 'dept' => 'MGMT', 'code' => 'EMP019'],
            ['name' => 'Kain', 'email' => 'kain@company.com', 'dept' => '3DD', 'code' => 'EMP020'],

            // R&D Department
            ['name' => 'Jaden', 'email' => 'jaden@company.com', 'dept' => 'RD', 'code' => 'EMP021'],

            // Marketing Department
            ['name' => 'Alina', 'email' => 'alina@company.com', 'dept' => 'MKT', 'code' => 'EMP022'],

            // Finance Department
            ['name' => 'Austin', 'email' => 'austin@company.com', 'dept' => 'FIN', 'code' => 'EMP023'],
            ['name' => 'Joelle', 'email' => 'joelle@company.com', 'dept' => 'FIN', 'code' => 'EMP024'],
            ['name' => 'Dan', 'email' => 'dan@company.com', 'dept' => 'PFIN', 'code' => 'EMP025'],
            ['name' => 'Nas', 'email' => 'nas@company.com', 'dept' => 'REFIN', 'code' => 'EMP026'],

            // Logistics Department
            ['name' => 'Gabriel', 'email' => 'gabriel@company.com', 'dept' => 'DISP1', 'code' => 'EMP027'],
            ['name' => 'Lana', 'email' => 'lana@company.com', 'dept' => 'DISP2', 'code' => 'EMP028'],
            ['name' => 'John', 'email' => 'john@company.com', 'dept' => 'DISP3', 'code' => 'EMP029'],
            ['name' => 'Nathan', 'email' => 'nathan@company.com', 'dept' => 'HR', 'code' => 'EMP030'],
            ['name' => 'Zeina', 'email' => 'zeina@company.com', 'dept' => 'HIRE', 'code' => 'EMP031'],

            // Pizza Department Supervisors
            ['name' => 'Alen', 'email' => 'alen@company.com', 'dept' => 'PIZZAOPS', 'code' => 'EMP032'],
            ['name' => 'Lilly', 'email' => 'lilly@company.com', 'dept' => 'PIZZAOPS', 'code' => 'EMP033'],
            ['name' => 'Petter', 'email' => 'petter@company.com', 'dept' => 'PIZZAOPS', 'code' => 'EMP034'],
            ['name' => 'William', 'email' => 'william@company.com', 'dept' => 'PIZZAOPS', 'code' => 'EMP035'],
            ['name' => 'Dylan', 'email' => 'dylan@company.com', 'dept' => 'PIZZAOPS', 'code' => 'EMP036'],
            ['name' => 'Joey', 'email' => 'joey@company.com', 'dept' => 'PIZZAOPS', 'code' => 'EMP037'],
            ['name' => 'Lisa', 'email' => 'lisa@company.com', 'dept' => 'PIZZAAUD', 'code' => 'EMP038'],

            // Pizza Project Managers
            ['name' => 'Anna', 'email' => 'anna@company.com', 'dept' => 'PIZZAPM', 'code' => 'EMP039'],
            ['name' => 'Eve', 'email' => 'eve@company.com', 'dept' => 'PIZZAPM', 'code' => 'EMP040'],
            ['name' => 'Cyntia', 'email' => 'cyntia@company.com', 'dept' => 'PIZZAPM', 'code' => 'EMP041'],
        ];

        foreach ($l2Users as $userData) {
            User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
                'role' => 'user',
                'user_level_id' => $levels['L2']->id,
                'department_id' => $departments[$userData['dept']]->id,
                'status' => 'active',
                'employee_code' => $userData['code'],
            ]);
        }

        // L1 Employees (subset - you can add more)
        $l1Users = [
            // Builders Department
            ['name' => 'Alaa', 'email' => 'alaa@company.com', 'dept' => 'BUILD', 'code' => 'EMP042'],
            ['name' => 'Dina', 'email' => 'dina@company.com', 'dept' => 'BUILD', 'code' => 'EMP043'],
            ['name' => 'Arya', 'email' => 'arya@company.com', 'dept' => 'BUILD', 'code' => 'EMP044'],
            ['name' => 'Eyan', 'email' => 'eyan@company.com', 'dept' => 'BUILD', 'code' => 'EMP045'],
            ['name' => 'Matt', 'email' => 'matt@company.com', 'dept' => 'BUILD', 'code' => 'EMP046'],
            ['name' => 'Harmony', 'email' => 'harmony@company.com', 'dept' => 'BUILD', 'code' => 'EMP047'],

            // Management Department
            ['name' => 'Ariana', 'email' => 'ariana@company.com', 'dept' => 'MGMT', 'code' => 'EMP048'],
            ['name' => 'Raya', 'email' => 'raya@company.com', 'dept' => 'MGMT', 'code' => 'EMP049'],

            // 3D Design
            ['name' => 'Norman', 'email' => 'norman@company.com', 'dept' => '3DD', 'code' => 'EMP050'],
            ['name' => 'Suzan', 'email' => 'suzan@company.com', 'dept' => '3DD', 'code' => 'EMP051'],

            // R&D Department
            ['name' => 'Maria', 'email' => 'maria@company.com', 'dept' => 'RD', 'code' => 'EMP052'],
            ['name' => 'Charlie', 'email' => 'charlie@company.com', 'dept' => 'RD', 'code' => 'EMP053'],
            ['name' => 'Kami', 'email' => 'kami@company.com', 'dept' => 'RD', 'code' => 'EMP054'],
            ['name' => 'Adler', 'email' => 'adler@company.com', 'dept' => 'RD', 'code' => 'EMP055'],

            // Marketing Department
            ['name' => 'Jane', 'email' => 'jane@company.com', 'dept' => 'MKT', 'code' => 'EMP056'],
            ['name' => 'Nelly', 'email' => 'nelly@company.com', 'dept' => 'MKT', 'code' => 'EMP057'],
            ['name' => 'Danial', 'email' => 'danial@company.com', 'dept' => 'MKT', 'code' => 'EMP058'],
            ['name' => 'Ned', 'email' => 'ned@company.com', 'dept' => 'MKT', 'code' => 'EMP059'],

            // Finance Departments
            ['name' => 'Grace', 'email' => 'grace@company.com', 'dept' => 'PFIN', 'code' => 'EMP060'],
            ['name' => 'Angelo', 'email' => 'angelo@company.com', 'dept' => 'PFIN', 'code' => 'EMP061'],
            ['name' => 'Scarlet', 'email' => 'scarlet@company.com', 'dept' => 'PFIN', 'code' => 'EMP062'],
            ['name' => 'Desiree', 'email' => 'desiree@company.com', 'dept' => 'PFIN', 'code' => 'EMP063'],
            ['name' => 'Hailey', 'email' => 'hailey@company.com', 'dept' => 'REFIN', 'code' => 'EMP064'],
            ['name' => 'Jack', 'email' => 'jack@company.com', 'dept' => 'REFIN', 'code' => 'EMP065'],
            ['name' => 'Tania', 'email' => 'tania@company.com', 'dept' => 'REFIN', 'code' => 'EMP066'],

            // Logistics Dispatch Teams
            ['name' => 'Andy', 'email' => 'andy@company.com', 'dept' => 'DISP1', 'code' => 'EMP067'],
            ['name' => 'Alvin', 'email' => 'alvin@company.com', 'dept' => 'DISP1', 'code' => 'EMP068'],
            ['name' => 'AJ', 'email' => 'aj@company.com', 'dept' => 'DISP1', 'code' => 'EMP069'],
            ['name' => 'Creed', 'email' => 'creed@company.com', 'dept' => 'DISP1', 'code' => 'EMP070'],

            ['name' => 'Carla', 'email' => 'carla@company.com', 'dept' => 'DISP2', 'code' => 'EMP071'],
            ['name' => 'Johnny', 'email' => 'johnny@company.com', 'dept' => 'DISP2', 'code' => 'EMP072'],
            ['name' => 'Josie', 'email' => 'josie@company.com', 'dept' => 'DISP2', 'code' => 'EMP073'],
            ['name' => 'Issac', 'email' => 'issac@company.com', 'dept' => 'DISP2', 'code' => 'EMP074'],
            ['name' => 'Jaxon', 'email' => 'jaxon@company.com', 'dept' => 'DISP2', 'code' => 'EMP075'],

            ['name' => 'Carl', 'email' => 'carl@company.com', 'dept' => 'DISP3', 'code' => 'EMP076'],
            ['name' => 'Eddie', 'email' => 'eddie@company.com', 'dept' => 'DISP3', 'code' => 'EMP077'],
            ['name' => 'Edmund', 'email' => 'edmund@company.com', 'dept' => 'DISP3', 'code' => 'EMP078'],
            ['name' => 'Mark', 'email' => 'mark@company.com', 'dept' => 'DISP3', 'code' => 'EMP079'],
            ['name' => 'Adam', 'email' => 'adam@company.com', 'dept' => 'DISP3', 'code' => 'EMP080'],
            ['name' => 'Liam', 'email' => 'liam@company.com', 'dept' => 'DISP3', 'code' => 'EMP081'],
            ['name' => 'Leonard', 'email' => 'leonard@company.com', 'dept' => 'DISP3', 'code' => 'EMP082'],
            ['name' => 'Ricard', 'email' => 'ricard@company.com', 'dept' => 'DISP3', 'code' => 'EMP083'],

            // HR and Hiring Teams
            ['name' => 'Ash', 'email' => 'ash@company.com', 'dept' => 'HR', 'code' => 'EMP084'],
            ['name' => 'Daisy', 'email' => 'daisy@company.com', 'dept' => 'HR', 'code' => 'EMP085'],
            ['name' => 'Allen', 'email' => 'allen@company.com', 'dept' => 'HIRE', 'code' => 'EMP086'],
            ['name' => 'Philip', 'email' => 'philip@company.com', 'dept' => 'HIRE', 'code' => 'EMP087'],
            ['name' => 'Raegan', 'email' => 'raegan@company.com', 'dept' => 'HIRE', 'code' => 'EMP088'],
            ['name' => 'Steven', 'email' => 'steven@company.com', 'dept' => 'HIRE', 'code' => 'EMP089'],

            // Pizza Store Managers (sample)
            ['name' => 'Fedel', 'email' => 'fedel@company.com', 'dept' => 'PIZZAOPS', 'code' => 'EMP090'],
            ['name' => 'Emily', 'email' => 'emily@company.com', 'dept' => 'PIZZAOPS', 'code' => 'EMP091'],
            ['name' => 'Jenny', 'email' => 'jenny@company.com', 'dept' => 'PIZZAOPS', 'code' => 'EMP092'],
            ['name' => 'Alison', 'email' => 'alison@company.com', 'dept' => 'PIZZAOPS', 'code' => 'EMP093'],
            ['name' => 'Georgina', 'email' => 'georgina@company.com', 'dept' => 'PIZZAOPS', 'code' => 'EMP094'],

            // Pizza Hiring Department
            ['name' => 'Raven', 'email' => 'raven@company.com', 'dept' => 'PIZZAHIRE', 'code' => 'EMP095'],
            ['name' => 'Tyler', 'email' => 'tyler@company.com', 'dept' => 'PIZZAHIRE', 'code' => 'EMP096'],
            ['name' => 'Jessica', 'email' => 'jessica@company.com', 'dept' => 'PIZZAHIRE', 'code' => 'EMP097'],
            ['name' => 'Avril', 'email' => 'avril@company.com', 'dept' => 'PIZZAHIRE', 'code' => 'EMP098'],
            ['name' => 'Jennifer', 'email' => 'jennifer@company.com', 'dept' => 'PIZZAHIRE', 'code' => 'EMP099'],

            // Pizza Audit Department
            ['name' => 'Alice', 'email' => 'alice@company.com', 'dept' => 'PIZZAAUD', 'code' => 'EMP100'],
            ['name' => 'Simon', 'email' => 'simon@company.com', 'dept' => 'PIZZAAUD', 'code' => 'EMP101'],
            ['name' => 'Natalie', 'email' => 'natalie@company.com', 'dept' => 'PIZZAAUD', 'code' => 'EMP102'],
        ];

        foreach ($l1Users as $userData) {
            User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),

                'user_level_id' => $levels['L1']->id,
                'department_id' => $departments[$userData['dept']]->id,
                'status' => 'active',
                'employee_code' => $userData['code'],
            ]);
        }
    }
}
