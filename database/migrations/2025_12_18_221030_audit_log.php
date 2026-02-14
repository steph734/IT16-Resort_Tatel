<?php

use illuminate\database\migrations\migration;
use illuminate\database\schema\blueprint;
use illuminate\support\facades\schema;

return new class extends migration
{
    /**
     * run the migrations.
     */
    public function up(): void
    {
        schema::create('audit_log', function (blueprint $table) {
            $table->bigincrements('id');
            $table->string('user_id', 8)->nullable();
            $table->string('action')->index();
            $table->text('description')->nullable();
            $table->ipaddress('ip_address')->nullable();
            $table->timestamps();

            // foreign key: deleting a log does not affect the user
            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->nullondelete(); // if user is deleted, set user_id to null in logs
        });
    }

    /**
     * reverse the migrations.
     */
    public function down(): void
    {
        schema::dropifexists('audit_log');
    }
};
