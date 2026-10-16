<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class StoreDocumentCollectionTimestamp extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE document_requests MODIFY collection_date DATETIME NULL');
    }

    public function down()
    {
        DB::statement('ALTER TABLE document_requests MODIFY collection_date DATE NULL');
    }
}