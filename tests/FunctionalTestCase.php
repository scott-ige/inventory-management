<?php

namespace Stevebauman\Inventory\Tests;

use Illuminate\Database\Capsule\Manager as DB;
use PHPUnit\Framework\TestCase;
use Stevebauman\Inventory\Models\Inventory;
use Stevebauman\Inventory\Models\InventoryStock;
use Stevebauman\Inventory\Models\Location;
use Stevebauman\Inventory\Models\Metric;
use Stevebauman\Inventory\Models\Category;
use Stevebauman\Inventory\Models\Supplier;
// use Stevebauman\Inventory\Models\InventoryTransaction;
// use Stevebauman\Inventory\Models\InventoryTransactionHistory;
use Illuminate\Database\Eloquent\Model as Eloquent;

abstract class FunctionalTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureDatabase();
        $this->migrateTables();

        Eloquent::unguard();
    }
    
    private function configureDatabase()
    {
        $db = new DB();

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ]);

        $db->bootEloquent();

        $db->setAsGlobal();
    }

    private function migrateTables()
    {
        DB::schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
        });

        DB::schema()->create('metrics', function ($table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('created_by')->unsigned()->nullable();
            $table->string('name');
            $table->string('symbol');

            $table->foreign('created_by')->references('id')->on('users')
                ->onUpdate('restrict')
                ->onDelete('set null');
        });

        DB::schema()->create('categories', function ($table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('parent_id')->nullable()->index();
            $table->integer('lft')->nullable()->index();
            $table->integer('rgt')->nullable()->index();
            $table->integer('depth')->nullable();
            $table->string('name');

            /*
             * This field is for scoping categories, use it if you
             * want to store multiple nested sets on the same table
             */
            $table->string('belongs_to')->nullable();
        });

        DB::schema()->create('locations', function ($table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('parent_id')->nullable()->index();
            $table->integer('lft')->nullable()->index();
            $table->integer('rgt')->nullable()->index();
            $table->integer('depth')->nullable();
            $table->string('name');

            /*
             * This field is for scoping categories, use it if you
             * want to store multiple nested sets on the same table
             */
            $table->string('belongs_to')->nullable();
        });

        DB::schema()->create('inventories', function ($table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->integer('category_id')->unsigned()->nullable();
            $table->integer('created_by')->unsigned()->nullable();
            $table->integer('metric_id')->unsigned();
            $table->string('name');
            $table->text('description')->nullable();

            $table->foreign('category_id')->references('id')->on('categories')
                ->onUpdate('restrict')
                ->onDelete('set null');

            $table->foreign('created_by')->references('id')->on('users')
                ->onUpdate('restrict')
                ->onDelete('set null');

            $table->foreign('metric_id')->references('id')->on('metrics')
                ->onUpdate('restrict')
                ->onDelete('cascade');
        });

        DB::schema()->create('inventory_stocks', function ($table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('created_by')->unsigned()->nullable();
            $table->integer('inventory_id')->unsigned();
            $table->integer('location_id')->unsigned();
            $table->decimal('quantity', 8, 2)->default(0);
            $table->string('aisle')->nullable();
            $table->string('row')->nullable();
            $table->string('bin')->nullable();

            /*
             * This allows only one stock to be created
             * on a single location
             */
            $table->unique(['inventory_id', 'location_id']);

            $table->foreign('created_by')->references('id')->on('users')
                ->onUpdate('restrict')
                ->onDelete('set null');

            $table->foreign('inventory_id')->references('id')->on('inventories')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('location_id')->references('id')->on('locations')
                ->onUpdate('restrict')
                ->onDelete('cascade');
        });

        DB::schema()->create('inventory_stock_movements', function ($table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('stock_id')->unsigned();
            $table->integer('created_by')->unsigned()->nullable();
            $table->decimal('before', 8, 2)->default(0);
            $table->decimal('after', 8, 2)->default(0);
            $table->decimal('cost', 8, 2)->default(0)->nullable();
            $table->string('reason')->nullable();

            $table->foreign('stock_id')->references('id')->on('inventory_stocks')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('created_by')->references('id')->on('users')
                ->onUpdate('restrict')
                ->onDelete('set null');
        });

        DB::schema()->create('inventory_skus', function ($table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('inventory_id')->unsigned();
            $table->string('code', 20);

            $table->foreign('inventory_id')->references('id')->on('inventories')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            /*
             * Make sure each SKU is unique
             */
            $table->unique(['code']);
        });

        DB::schema()->create('suppliers', function ($table) {
            $table->increments('id');
            $table->timestamps();

            $table->string('name');
            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('contact_title')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_fax')->nullable();
            $table->string('contact_email')->nullable();
        });

        DB::schema()->create('inventory_suppliers', function ($table) {
            $table->increments('id');
            $table->timestamps();

            $table->integer('inventory_id')->unsigned();
            $table->integer('supplier_id')->unsigned();

            $table->foreign('inventory_id')->references('id')->on('inventories')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('supplier_id')->references('id')->on('suppliers')
                ->onUpdate('restrict')
                ->onDelete('cascade');
        });

        DB::schema()->create('inventory_transactions', function ($table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('created_by')->unsigned()->nullable();
            $table->integer('stock_id')->unsigned();
            $table->string('name')->nullable();
            $table->string('state');
            $table->decimal('quantity', 8, 2)->default(0);

            $table->foreign('created_by')->references('id')->on('users')
                ->onUpdate('restrict')
                ->onDelete('set null');

            $table->foreign('stock_id')->references('id')->on('inventory_stocks')
                ->onUpdate('restrict')
                ->onDelete('cascade');
        });

        DB::schema()->create('inventory_transaction_histories', function ($table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('created_by')->unsigned()->nullable();
            $table->integer('transaction_id')->unsigned();

            /*
             * Allows tracking states for each transaction
             */
            $table->string('state_before');
            $table->string('state_after');

            /*
             * Allows tracking the quantities of each transaction
             */
            $table->string('quantity_before');
            $table->string('quantity_after');

            $table->foreign('created_by')->references('id')->on('users')
                ->onUpdate('restrict')
                ->onDelete('set null');

            $table->foreign('transaction_id')->references('id')->on('inventory_transactions')
                ->onUpdate('restrict')
                ->onDelete('cascade');
        });

        DB::schema()->table('inventories', function ($table) {
            $table->integer('parent_id')->unsigned()->nullable()->after('id');

            $table->foreign('parent_id')->references('id')->on('inventories')
                ->onUpdate('restrict')
                ->onDelete('cascade');
        });

        DB::schema()->table('inventories', function ($table) {
            $table->boolean('is_assembly')->default(false);
            $table->boolean('is_bundle')->default(false);
        });

        DB::schema()->create('inventory_assemblies', function ($table) {

            $table->increments('id');
            $table->timestamps();
            $table->integer('inventory_id')->unsigned();
            $table->integer('part_id')->unsigned();
            $table->integer('quantity')->nullable();

            // Extra column for testing
            $table->string('extra')->nullable();

            $table->foreign('inventory_id')->references('id')->on('inventories')->onDelete('cascade');
            $table->foreign('part_id')->references('id')->on('inventories')->onDelete('cascade');
        });

        DB::schema()->create('inventory_bundles', function ($table) {

            $table->increments('id');
            $table->timestamps();
            $table->integer('inventory_id')->unsigned();
            $table->integer('component_id')->unsigned();
            $table->integer('quantity')->nullable();

            // Extra column for testing
            $table->string('extra')->nullable();

            $table->foreign('inventory_id')->references('id')->on('inventories')->onDelete('cascade');
            $table->foreign('component_id')->references('id')->on('inventories')->onDelete('cascade');
        });
    }

    /**
     * @param array $attributes
     *
     * @return Inventory
     */
    protected function newInventory(array $attributes = [])
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        if(count($attributes) > 0) {
            return Inventory::create($attributes);
        }

        return Inventory::create([
            'metric_id' => $metric->id,
            'category_id' => $category->id,
            'name' => 'Milk',
            'description' => 'Delicious Milk',
        ]);
    }

    /**
     * @return Metric
     */
    protected function newMetric()
    {
        return Metric::create([
            'name' => 'Litres',
            'symbol' => 'L',
        ]);
    }

    /**
     * @return Location
     */
    protected function newLocation()
    {
        return Location::create([
            'name' => 'Warehouse',
            'belongs_to' => '',
        ]);
    }

    /**
     * @return Category
     */
    protected function newCategory()
    {
        return Category::create([
            'name' => 'Drinks',
        ]);
    }

    /**
     * @return Supplier
     */
    protected function newSupplier()
    {
        return Supplier::create([
            'name' => 'Supplier',
            'address' => '123 Fake St',
            'postal_code' => '12345',
            'zip_code' => '12345',
            'region' => 'ON',
            'city' => 'Toronto',
            'country' => 'Canada',
            'contact_title' => 'Manager',
            'contact_name' => 'John Doe',
            'contact_phone' => '555 555 5555',
            'contact_fax' => '555 555 5555',
            'contact_email' => 'john.doe@email.com',
        ]);
    }

    /**
     * @return InventoryStock
     */
    protected function newInventoryStock()
    {
        $item = $this->newInventory();

        $location = $this->newLocation();

        $stock = new InventoryStock();
        $stock->inventory_id = $item->id;
        $stock->location_id = $location->id;
        $stock->quantity = 20;
        $stock->cost = '5.20';
        $stock->reason = 'I bought some';
        $stock->save();

        return $stock;
    }

    /**
     * @return InventorySku
     */
    protected function newInventorySku()
    {
        $item = $this->newInventory();

        return $item->generateSku();
    }

    /**
     * @return InventoryStockTransaction
     */
    protected function newTransaction()
    {
        $stock = $this->newInventoryStock();

        return $stock->newTransaction();
    }
}
