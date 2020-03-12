<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
/*

        $this->call(RoleSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(ProfileSeeder::class);
        $this->call(PersonSeeder::class);
        $this->call(UnitSeeder::class);
        $this->call(PaytermSeeder::class);
        $this->call(Item2Seeder::class);
        $this->call(PriceSeeder::class);
        $this->call(FreezerSeeder::class);
        $this->call(AccessorySeeder::class);
        $this->call(GeneralSettingSeeder::class);
        $this->call(D2dOnlineSaleSeeder::class);
        $this->call(MonthSeeder::class);
        $this->call(CustcategorySeeder::class);
        $this->call(ItemcategorySeeder::class);
        $this->call(ProfileUserRelateSeeder::class);
        $this->call(InternalBillingSeeder::class);
        $this->call(CurrencySeeder::class);
        $this->call(VendingSeeder::class);
        $this->call(RoleFranchiseeSeeder::class);
        $this->call(UserRoleAccountAdmin::class);
        $this->call(SupervisorMsiaSeeder::class);
         */
        // $this->call(SubFranchiseeSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(RoleSeeder::class);
        Model::reguard();
    }
}
