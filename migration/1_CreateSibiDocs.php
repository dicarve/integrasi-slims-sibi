<?php
use SLiMS\Table\Schema;
use SLiMS\Table\Blueprint;
use SLiMS\DB;

class CreateSibiDocs extends \SLiMS\Migration\Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    function up()
    {
        Schema::create('sibi_docs', function (Blueprint $table) {
            // Engine and Collation
            $table->engine = 'MyISAM';
            $table->charset = 'utf8mb4'; 
            $table->collation = 'utf8mb4_unicode_ci';

            // Core String Fields
            $table->number('id')->notNull();
            $table->text('title')->notNull();
            $table->string('slug', 100)->notNull();
            $table->text('image')->nullable();
            $table->text('attachment')->nullable();
            $table->text('description')->nullable();

            // Metadata
            $table->date('published_year')->nullable();
            $table->string('class', 50)->nullable();
            $table->string('level', 100)->nullable();
            $table->text('writer')->nullable();
            $table->text('reviewer')->nullable();
            $table->string('translator', 255)->nullable();
            $table->string('adapter', 255)->nullable();
            $table->string('designer', 255)->nullable();
            $table->string('cover_designer', 255)->nullable();
            $table->string('ilustrator', 255)->nullable();
            $table->string('editor', 255)->nullable();
            $table->string('aligner', 255)->nullable();
            $table->string('publisher', 255)->nullable();
            $table->text('contributor')->nullable();
            $table->string('language', 100)->nullable();
            $table->text('context')->nullable();
            $table->string('subject', 100)->nullable();
            $table->string('format', 50)->nullable();
            $table->string('isbn', 50)->nullable();
            $table->string('curriculum', 100)->nullable();
            $table->string('collation', 100)->nullable();
            $table->string('type', 50)->nullable();
            $table->string('edition', 50)->nullable();
            $table->string('unit', 100)->nullable();
            $table->string('status', 50)->nullable();
            $table->string('category', 100)->nullable();
            $table->string('book_type', 100)->nullable();
            $table->string('version', 20)->nullable();

            // Pricing Fields
            $table->decimal('price_zone_1', 10, 2)->nullable();
            $table->decimal('price_zone_2', 10, 2)->nullable();
            $table->decimal('price_zone_3', 10, 2)->nullable();
            $table->decimal('price_zone_4', 10, 2)->nullable();
            $table->decimal('price_zone_5A', 10, 2)->nullable();
            $table->decimal('price_zone_5B', 10, 2)->nullable();

            // Timestamps
            $table->datetime('created_at')->nullable();
            $table->datetime('updated_at')->nullable();
            $table->datetime('deleted_at')->nullable();
            
            // indexes
            $table->primary('id');
            $table->fulltext('title');
            $table->fulltext('description');
            $table->fulltext('writer');
            $table->index('isbn');
            $table->index('slug');
            $table->index('category');
        });

        Schema::table('biblio', function (Blueprint $table) {
            $table->number('sibi_doc_id')->add();
            $table->unique('sibi_doc_id')->add();
            $table->string('image', 255)->nullable()->change();
        });

        Schema::table('search_biblio', function (Blueprint $table) {
            $table->string('image', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    function down()
    {
        Schema::drop('sibi_docs');
        Schema::dropColumn('biblio', 'sibi_doc_id');
        Schema::dropIndex('biblio', 'sibi_doc_id_unq');
    }
}