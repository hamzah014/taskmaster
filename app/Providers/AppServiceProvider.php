<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Define the custom validation rule
        Validator::extend('at_least_one_not_null', function ($attribute, $value, $parameters, $validator) {
            // Check if at least one element in the array is not null
            return count(array_filter($value, function ($item) {
                    return $item !== null;
                })) > 0;
        });

        Validator::extend('starts_with_01', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^01/', $value) === 1;
        });

        // Optionally, you can define a custom error message for this rule
//        Validator::replacer('at_least_one_not_null', function ($message, $attribute, $rule, $parameters) {
//            return str_replace(':attribute', $attribute, 'At least one element in :attribute must not be null.');
//        });
    }


}
