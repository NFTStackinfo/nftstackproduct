<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contract', function (Blueprint $table) {
            $table->id();
            $table->string('collection_name', 64);
            $table->string('project_name', 64);
            $table->string('collection_symbol', 32);
            $table->string('metadata_uri', 128);
            $table->string('mainnet_address', 42)->nullable();
            $table->string('rinkeby_address', 42)->nullable();
            $table->float('mint_price');
            $table->float('presale_mint_price')->nullable();
            $table->smallInteger('total_count');
            $table->smallInteger('reserve_count');
            $table->smallInteger('limit_per_transaction');
            $table->smallInteger('limit_per_wallet');
            $table->smallInteger('presale_limit_per_wallet');
            $table->bigInteger('user_id');
            $table->bigInteger('chain_id');
            $table->bigInteger('type_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contract');
    }
};
