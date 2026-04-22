<?php

/**
 * @author Erick Escobar
 * @license MIT
 * @version 1.4.0
 *
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = config('caronte.table_prefix') . 'UsersMetadata';

        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->string('uri_user', 40);
                $table->string('scope', 128);
                $table->string('key', 45);
                $table->string('value', 45);
                $table->primary(['uri_user', 'scope', 'key']);
                $table->engine = 'InnoDB';
            });
        } else {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                // Ensure columns exist and have correct types/lengths
                if (!Schema::hasColumn($tableName, 'uri_user')) {
                    $table->string('uri_user', 40);
                } else {
                    $table->string('uri_user', 40)->change();
                }
                if (!Schema::hasColumn($tableName, 'scope')) {
                    $table->string('scope', 128);
                } else {
                    $table->string('scope', 128)->change();
                }
                if (!Schema::hasColumn($tableName, 'key')) {
                    $table->string('key', 45);
                } else {
                    $table->string('key', 45)->change();
                }
                if (!Schema::hasColumn($tableName, 'value')) {
                    $table->string('value', 45);
                } else {
                    $table->string('value', 45)->change();
                }
                // Drop and re-add primary key if needed
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes($tableName);
                if (isset($indexes['primary']) && $indexes['primary']->getColumns() !== ['uri_user', 'scope', 'key']) {
                    $table->dropPrimary();
                    $table->primary(['uri_user', 'scope', 'key']);
                }
                $table->engine = 'InnoDB';
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('caronte.table_prefix') . 'UsersMetadata';
        Schema::dropIfExists($tableName);
    }
};
