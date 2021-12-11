<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     $request->user()->getRoleNames();
//     return $request->user();
// });

// Route::middleware('auth:api')->delete('/logout', function (Request $request) {
//     $request->user()->token()->revoke();

//     return response()->json([
//         'status' => true,
//         'code' => 200,
//         'message' => 'Anda berhasil logout..'
//     ]);
// });


/**
 * This is auth routes
 */
Route::group([
    'prefix' => 'auth'
], function () {
    Route::post('login', 'AuthController@login');
    Route::post('register', 'AuthController@register');
    Route::group([
        'middleware' => 'auth:api'
    ], function() {
        Route::post('register-cashier', 'AuthController@registerCashier');
        Route::delete('logout', 'AuthController@logout');
        Route::get('user', 'AuthController@user');
    });
});


/**
 * This is store routes
 */
Route::get('/stores', 'Owner\StoreController@getAllStore')->name('getAllStore');
Route::get('/stores/search', 'Owner\StoreController@getAllStoreWithFilter')->name('getAllStoreWithFilter');
Route::get('/stores/{id}', 'Owner\StoreController@findStoreById')->name('findStoreById');
Route::get('/stores/seat/{id}', 'Owner\StoreController@getSeat')->name('getSeat');
Route::group(['prefix' => 'stores', 'middleware' => 'auth:api'], function()
{
    Route::post('/mystore', 'Owner\StoreController@getStoreByOwner')->name('getStoreByOwner');
    Route::post('/create-store', 'Owner\StoreController@createStore')->name('createStore');
    Route::put('/{id}', 'Owner\StoreController@updateStore')->name('updateStore');
    Route::post('/seat/{id}', 'Owner\StoreController@setSeat')->name('setSeat');
    Route::put('/seat/{id}/{index}', 'Owner\StoreController@emptySeat')->name('emptySeat');
    Route::put('/seat/{id}', 'Owner\StoreController@emptyAllSeat')->name('emptyAllSeat');
    Route::post('/{id}/update-catalog', 'Owner\StoreController@updateCatalogOnStore')->name('updateCatalogOnStore');
    Route::post('/{id}/update-cashier', 'Owner\StoreController@updateCashierOnStore')->name('updateCashierOnStore');
    Route::delete('/{id}', 'Owner\StoreController@deleteStore')->name('deleteStore');
});

/**
 * This is catalog routes
 */
Route::get('/catalogs', 'Owner\CatalogController@getAllCatalog')->name('getAllCatalog');
Route::get('/catalogs/search', 'Owner\CatalogController@getAllCatalogWithFilter')->name('getAllCatalogWithFilter');
Route::get('/catalogs/owner/{id}', 'Owner\CatalogController@getCatalogByOwner')->name('getCatalogByOwner');
Route::get('/catalogs/category/{id}', 'Owner\CatalogController@getCatalogWithCategory')->name('getCatalogWithCategory');
Route::get('/catalogs/{id}', 'Owner\CatalogController@findCatalogById')->name('findCatalogById');
Route::get('/catalogs/stock/{id}', 'Owner\CatalogController@getCatalogStock')->name('getCatalogStock');
Route::group(['prefix' => 'catalogs', 'middleware' => 'auth:api'], function()
{
    Route::put('/stock/{id}', 'Owner\CatalogController@updateCatalogStock')->name('updateCatalogStock');
    Route::post('/create-catalog', 'Owner\CatalogController@createCatalog')->name('createCatalog');
    Route::put('/{id}', 'Owner\CatalogController@updateCatalog')->name('updateCatalog');
    Route::delete('/{id}', 'Owner\CatalogController@deleteCatalog')->name('deleteCatalog');
});

/**
 * This is cashier routes
 */
Route::group(['prefix' => 'cashiers', 'middleware' => 'auth:api'], function()
{
    Route::get('/', 'Owner\CashierController@getAllCashier')->name('getAllCashier');
    Route::get('/{id}', 'Owner\CashierController@findCashierById')->name('findCashierById');
    Route::get('/owner/{id}', 'Owner\CashierController@findCashierByOwner')->name('findCashierByOwner');
    Route::get('store/{id}', 'Owner\CashierController@findCashierByStore')->name('findCashierByStore');
    Route::put('/{id}', 'Owner\CashierController@updateCashier')->name('updateCashier');
    Route::delete('/{id}', 'Owner\CashierController@deleteCashier')->name('deleteCashier');
});

/**
 * This is order routes
 */
Route::group(['prefix' => 'order', 'middleware' => 'auth:api'], function()
{
    Route::get('/in/{id}', 'OrderController@getOrderIn')->name('getOrderIn');
    Route::get('/history/{id}', 'OrderController@getOrderHistory')->name('getOrderHistory');
    Route::get('/payment/{id}', 'OrderController@getPaymentOrder')->name('getPaymentOrder');
    Route::post('/create', 'OrderController@createOrder')->name('createCashier');
    Route::put('/delivered/{id}', 'OrderController@orderDelivered')->name('orderDelivered');
    Route::put('/done/{id}', 'OrderController@orderDone')->name('orderDone');
    Route::put('/payment/{id}', 'OrderController@paymentOrder')->name('paymentOrder');
    // Route::get('/{id}', 'Owner\CashierController@findCashierById')->name('findCashierById');
    // Route::delete('/{id}', 'Owner\CashierController@deleteCashier')->name('deleteCashier');
});


/**
 * This is statistik routes
 */
Route::group(['prefix' => 'stats', 'middleware' => 'auth:api'], function()
{
    Route::post('/order/today','StatsController@todayAll')->name('getToday');
    Route::get('/order/today/{id}','StatsController@today')->name('getTodayAll');
    Route::post('/order/week','StatsController@weekAll')->name('getWeekAll');
    Route::get('/order/week/{id}','StatsController@week')->name('getWeek');
    Route::post('/order/month','StatsController@monthAll')->name('getMonthAll');
    Route::get('/order/month/{id}','StatsController@month')->name('getMonth');
    Route::post('/order/year','StatsController@yearAll')->name('getYearAll');
    Route::get('/order/year/{id}','StatsController@year')->name('getYear');


    Route::post('/income/today' ,'IncomeStatsController@todayAll')->name('getInToday');
    Route::get('/income/today/{id}','IncomeStatsController@today')->name('getInTodayAll');
    Route::post('/income/week','IncomeStatsController@weekAll')->name('getInWeekAll');
    Route::get('/income/week/{id}','IncomeStatsController@week')->name('getInWeek');
    Route::post('/income/month','IncomeStatsController@monthAll')->name('getInMonthAll');
    Route::get('/income/month/{id}','IncomeStatsController@month')->name('getInMonth');
    Route::post('/income/year','IncomeStatsController@yearAll')->name('getInYearAll');
    Route::get('/income/year/{id}','IncomeStatsController@year')->name('getInYear');


});



