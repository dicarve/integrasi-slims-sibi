<?php
/**
 * @Created by          : Ari Nugraha (dicarve@gmail.com)
 * @Date                : 11/12/2025 00.00
 * @File name           : index.php
 */

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Exception\RequestException;
use SLiMS\DB;

global $dbs;

defined('INDEX_AUTH') OR die('Direct access not allowed!');

// IP based access limitation
require LIB . 'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-bibliography');
// start the session
require SB . 'admin/default/session.inc.php';
require SIMBIO . 'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO . 'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO . 'simbio_DB/datagrid/simbio_dbgrid.inc.php';

// privileges checking
$can_read = utility::havePrivilege('bibliography', 'r');

if (!$can_read) {
    die('<div class="errorBox">' . __('You are not authorized to view this section') . '</div>');
}

function httpQuery($query = [])
{
    return http_build_query(array_unique(array_merge($_GET, $query)));
}

function pluginQuery()
{
    $default_get = $_GET;
    $allowed_query = ['mod', 'id', '_']; 
    $default_get = array_filter($default_get, function ($key) use ($allowed_query) {
        return in_array($key, $allowed_query);
    }, ARRAY_FILTER_USE_KEY);

    return http_build_query(array_unique($default_get));
}

require_once __DIR__ . '/vendor/autoload.php';

// SIBI API base URI
$base_uri = 'https://api.buku.cloudapp.web.id/';
// API endpoints
$sibi_endpoint = [
    'Kurikulum Merdeka' => '/api/catalogue/getPenggerakTextBooks?limit=3000&type_pdf&order_by=updated_at',
    'Teks K-13' => '/api/catalogue/getTextBooks?limit=2000&type_pdf&order_by=updated_at',
    'Non-Teks' => '/api/catalogue/getNonTextBooks?limit=2000&type_pdf&order_by=updated_at&tag=Buku%20Model',
    'Buku STEM' => '/api/catalogue/getBooksByTag?Tag=STEM'
];
$book_type = [
    'buku_sekolah_penggerak' => 'Kurikulum Merdeka / Buku STEM',
    'buku_teks' => 'Teks K-13',
    'buku_non_teks' => 'Non-Teks'
];

$self_url = $_SERVER['PHP_SELF'].'?'.pluginQuery();
$action = $_GET['action'] ?? false;
$pdo = DB::getInstance();

// on print action
$records = [];
if ($action === 'harvest') {
    // utility::jsToastr(__('SIBI Harvesting'), __('Error in harvesting SIBI Data!'), 'error');
    // exit();

    // check type
    $type = $_GET['type'] ?? 'all';

    // utility::jsToastr(__('SIBI Harvesting'), __("Harvesting: $type"), 'success');
    // exit();

    $client = new Client(['base_uri' => $base_uri]);
    $stmt = $pdo->prepare(<<<SQL
        INSERT IGNORE INTO sibi_docs (
            id, title, slug, image, attachment, description, published_year, class, level, 
            writer, reviewer, translator, adapter, designer, cover_designer, ilustrator, 
            editor, aligner, publisher, contributor, language, context, subject, format, 
            isbn, curriculum, `collation`, type, edition, unit, status, category, 
            book_type, version, price_zone_1, price_zone_2, price_zone_3, price_zone_4, 
            price_zone_5A, price_zone_5B, created_at, updated_at, deleted_at
        ) VALUES (
            :id, :title, :slug, :image, :attachment, :description, :published_year, :class, :level, 
            :writer, :reviewer, :translator, :adapter, :designer, :cover_designer, :ilustrator, 
            :editor, :aligner, :publisher, :contributor, :language, :context, :subject, :format, 
            :isbn, :curriculum, :collation, :type, :edition, :unit, :status, :category, 
            :book_type, :version, :price_zone_1, :price_zone_2, :price_zone_3, :price_zone_4, 
            :price_zone_5A, :price_zone_5B, :created_at, :updated_at, :deleted_at
        )
    SQL);
    
    if ($type == 'all') {
        // Asycn non-blocking request to all endpoint
        $promises = [];
        foreach ($sibi_endpoint as $doctype => $api) :
            $promises[$doctype] = $client->getAsync($api);
        endforeach;
        $responses = Promise\Utils::settle($promises)->wait();

        foreach ($responses as $doctype => $response) :
            // var_dump($response);
            if ($response['state'] == 'fulfilled') {
                $body = $response['value']->getBody();
                $sibi_docs = json_decode($body, true);
                $results = $sibi_docs['results'];
                $rowsTotal = count($results);
                if ($rowsTotal > 0) {
                    $failed = 0;
                    $lastError = '';
                    // iterate the result and store to table
                    foreach ($results as $record) :
                        try {
                            // cleaning
                            if (isset($record['published_year']) && $record['published_year'] == '0000-00-00') {
                                $record['published_year'] = null;
                            }

                            $stmt->execute([
                                ':id'             => $record['id'],
                                ':title'          => $record['title'],
                                ':slug'           => $record['slug'],
                                ':image'          => $record['image'],
                                ':attachment'     => $record['attachment'],
                                ':description'    => $record['description'] ?? null,
                                ':published_year' => $record['published_year'] ?? null,
                                ':class'          => $record['class'] ?? null,
                                ':level'          => $record['level'] ?? null,
                                ':writer'         => $record['writer'] ?? null,
                                ':reviewer'       => $record['reviewer'] ?? null,
                                ':translator'     => $record['translator'] ?? null,
                                ':adapter'        => $record['adapter'] ?? null,
                                ':designer'       => $record['designer'] ?? null,
                                ':cover_designer' => $record['cover_designer'] ?? null,
                                ':ilustrator'     => $record['ilustrator'] ?? null,
                                ':editor'         => $record['editor'] ?? null,
                                ':aligner'        => $record['aligner'] ?? null,
                                ':publisher'      => $record['publisher'] ?? null,
                                ':contributor'    => $record['contributor'] ?? null,
                                ':language'       => $record['language'] ?? null,
                                ':context'        => $record['context'] ?? null,
                                ':subject'        => $record['subject'] ?? null,
                                ':format'         => $record['format'] ?? null,
                                ':isbn'           => $record['isbn'] ?? null,
                                ':curriculum'     => $record['curriculum'] ?? null,
                                ':collation'      => $record['collation'] ?? null,
                                ':type'           => $record['type'] ?? null,
                                ':edition'        => $record['edition'] ?? null,
                                ':unit'           => $record['unit'] ?? null,
                                ':status'         => $record['status'] ?? null,
                                ':category'       => $record['category'] ?? null,
                                ':book_type'      => $record['book_type'] ?? null,
                                ':version'        => $record['version'] ?? null,
                                ':price_zone_1'   => $record['price_zone_1'] ?? null,
                                ':price_zone_2'   => $record['price_zone_2'] ?? null,
                                ':price_zone_3'   => $record['price_zone_3'] ?? null,
                                ':price_zone_4'   => $record['price_zone_4'] ?? null,
                                ':price_zone_5A'  => $record['price_zone_5A'] ?? null,
                                ':price_zone_5B'  => $record['price_zone_5B'] ?? null,
                                ':created_at'     => $record['created_at'] ?? null,
                                ':updated_at'     => $record['updated_at'] ?? null,
                                ':deleted_at'     => $record['deleted_at'] ?? null
                            ]);

                        } catch (PDOException $e) {
                            $failed++;
                            $lastError = $e->getMessage();
                        }
                    endforeach;
                    if ($failed > 0) {
                        utility::jsToastr(__('SIBI Harvesting'), __("$failed documents failed to get from $doctype. Last error: $lastError"), 'warning');
                    }
                    $rowsStored = $rowsTotal-$failed;
                    utility::jsToastr(__('SIBI Harvesting'), __("Harvested $rowsStored (from total of $rowsTotal) documents from $doctype"), 'success');
                }
            } else {
                // var_dump($response);
                utility::jsToastr(__('SIBI Harvesting'), __("Request to $doctype FAILED with reason: ".$response['reason']->getMessage()), 'error');
            }

        endforeach;
    } else {
        // get records from selected endpoint
        
    }

    utility::jsToastr('SIBI', __('Harvesting finish'), 'success');
    echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(\''.$self_url.'\');</script>';
    exit();
}

if ($action === 'clearcache') {
    $error = '';
    $affected_rows = 0;
    try {
        try {
            $affected_rows = $pdo->exec("TRUNCATE sibi_docs");
        } catch (PDOException $e) {
            // do nothing here
        }
        $affected_rows = $pdo->exec("DELETE FROM sibi_docs");
        utility::jsToastr('SIBI', "Cache cleared. $affected_rows record(s) deleted.", 'success');
    } catch (PDOException $e) {
        $error = $e->getMessage();
        utility::jsToastr('SIBI', "Cache clearing error caused by: $error", 'error');
    }
    
    echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(\''.$self_url.'\');</script>';
    exit();
}

if ($action === 'syncbiblio') {
    $error = '';
    $inserted_rows = 0;
    try {
        // insert base biblio data
        $inserted_rows = $pdo->exec(<<<SQL
            REPLACE INTO biblio (
                source, gmd_id, content_type_id, media_type_id, carrier_type_id, title, sor, image, notes, publish_year, classification, 
                isbn_issn, `collation`, edition, spec_detail_info, input_date, last_update, sibi_doc_id
            ) SELECT 'SIBI', 1, 20, 2, 16, title, writer, image, description, YEAR(published_year), class, 
                isbn, `collation`, edition, category, created_at, updated_at, id FROM sibi_docs
        SQL);

        // insert authors data
        $affected_rows = $pdo->exec(<<<SQL
            INSERT IGNORE INTO mst_author (author_name, input_date)
            WITH RECURSIVE author_values AS (
                -- Get the first value and the remainder of the string
                SELECT 
                    SUBSTRING_INDEX(sor, ',', 1) AS each_author,
                    SUBSTRING(sor, LOCATE(',', sor) + 1) AS remainder
                FROM biblio
                WHERE sor != ''

                UNION ALL

                -- Recursive step: Keep peeling off the next value until remainder is empty
                SELECT 
                    SUBSTRING_INDEX(remainder, ',', 1) AS each_author,
                    IF(LOCATE(',', remainder) > 0, SUBSTRING(remainder, LOCATE(',', remainder) + 1), '') AS remainder
                FROM author_values
                WHERE each_author != '' AND remainder != ''
            )
            SELECT TRIM(each_author), NOW() FROM author_values
        SQL);

        // insert biblio authors relation data
        $affected_rows = $pdo->exec(<<<SQL
            INSERT IGNORE INTO biblio_author (biblio_id, author_id)
            WITH RECURSIVE author_values AS (
                -- Get the first value and the remainder of the string
                SELECT
                    biblio_id,
                    SUBSTRING_INDEX(sor, ',', 1) AS each_author,
                    SUBSTRING(sor, LOCATE(',', sor) + 1) AS remainder
                FROM biblio
                WHERE sor != ''

                UNION ALL

                -- Recursive  peeling off the next value until remainder is empty
                SELECT 
                    biblio_id,
                    SUBSTRING_INDEX(remainder, ',', 1) AS each_author,
                    IF(LOCATE(',', remainder) > 0, SUBSTRING(remainder, LOCATE(',', remainder) + 1), '') AS remainder
                FROM author_values
                WHERE each_author != '' AND remainder != ''
            )
            SELECT av.biblio_id, a.author_id FROM author_values av
            JOIN mst_author a ON av.each_author=a.author_name
        SQL);

        // insert topic data
        $affected_rows = $pdo->exec(<<<SQL
            INSERT IGNORE INTO mst_topic (topic, topic_type, classification)
            WITH RECURSIVE sibi_topics AS (
                SELECT REPLACE(category, "_", " ") as topic_value FROM sibi_docs WHERE category != ''

                UNION ALL

                SELECT REPLACE(book_type, "_", " ") as topic_value FROM sibi_docs WHERE book_type != ''

                UNION ALL

                SELECT REPLACE(`subject`, "_", " ") as topic_value FROM sibi_docs WHERE `subject` != ''

                UNION ALL

                SELECT REPLACE(`level`, "_", " ") as topic_value FROM sibi_docs WHERE `level` != ''
            )
            SELECT st.topic_value, 't', 'SIBI' FROM sibi_topics st
        SQL);

        utility::jsToastr('SIBI', "$inserted_rows record(s) synchronized to SLiMS.", 'success');
    } catch (PDOException $e) {
        $error = $e->getMessage();
        utility::jsToastr('SIBI', "Sync error caused by: $error", 'error');
    }
    
    echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(\''.$self_url.'\');</script>';
    exit();
}

if ($action === 'downloadfiles') {

    $sql = 'SELECT * FROM sibi_docs LIMIT 10000';
    $failed = 0;
    $success = 0;
    $error = '';
    $client = new Client();
    // statement for files table insertion
    $file_stmt = $pdo->prepare("INSERT INTO files (file_title , file_name, file_url, file_dir, mime_type) 
        VALUES (:file_title , :file_name, :file_url, :file_dir, :mime_type)");

    // query the sibi docs data
    foreach ($pdo->query($sql) as $row) {
        $fileUrl = $row['attachment'];
        $pdfName = urldecode( basename($row['attachment']) );
        $saveTo  = REPOBS . 'sibi_docs/' . $pdfName;
        
        try {
            // Use 'sink' to write the response directly to a file
            // This is memory efficient for large PDFs
            $response = $client->request('GET', $fileUrl, [
                'sink' => $saveTo,
                'verify' => true, // Set to false only if you have local SSL issues
            ]);

            // insert into files table
            $file_stmt->execute([
                ':file_title' => $row['title'], 
                ':file_name' => $pdfName, 
                ':file_url' => $fileUrl, 
                ':file_dir' => 'sibi_docs', 
                ':mime_type' => 'application/pdf' 
            ]);

            $success++;
        } catch (RequestException $e) {
            $failed++;
            $error = $e->getMessage();
        }
    }

    if ($success) {
        utility::jsToastr('SIBI', "$success PDF files(s) successfully downloaded", 'success');
    }

    if ($failed) {
        utility::jsToastr('SIBI', "$failed PDF files(s) failed to download caused by: $error", 'error');
    }
    exit();
}

if ($action === 'downloadfile') {
    if (!isset($_GET['sibi_doc_id']) || empty($_GET['sibi_doc_id'])) {
        exit();
    }

    $failed = 0;
    $success = 0;
    $error = '';

    // query the sibi doc data
    $doc_sql = 'SELECT sb.*, b.biblio_id FROM sibi_docs AS sb 
        LEFT JOIN biblio AS b ON sb.id=b.sibi_doc_id 
        WHERE sb.id=:id';
    $doc = $pdo->prepare($doc_sql);
    $doc->execute(['id' => $_GET['sibi_doc_id']]);
    $row = $doc->fetch(PDO::FETCH_ASSOC);

    // statement for files table insertion
    $file_stmt = $pdo->prepare("INSERT INTO files (file_title , file_name, file_url, file_dir, mime_type, input_date, last_update, uploader_id) 
        VALUES (:file_title , :file_name, :file_url, :file_dir, :mime_type, NOW(), NOW(), :uploader_id)");
    $biblio_att_stmt = $pdo->prepare("INSERT IGNORE INTO biblio_attachment (file_id , biblio_id, placement , access_type) 
        VALUES (:file_id , :biblio_id, 'popup', 'public')");

    $fileUrl = $row['attachment'];
    $biblio_id = $row['biblio_id'];
    $pdfName = urldecode( basename($fileUrl) );
    $saveTo  = REPOBS . 'sibi_docs/' . $pdfName;

    $client = new Client();
    
    try {
        // Use 'sink' to write the response directly to a file
        $response = $client->request('GET', $fileUrl, [
            'sink' => $saveTo,
            'verify' => true, // Set to false only if you have local SSL issues
        ]);

        // insert into files table
        $file_stmt->execute([
            'file_title' => $row['title'], 
            'file_name' => $pdfName, 
            'file_url' => $fileUrl, 
            'file_dir' => 'sibi_docs', 
            'mime_type' => 'application/pdf',
            'uploader_id' => $_SESSION['uid']
        ]);

        // insert biblio attachment
        if ($biblio_id) {
            $file_id = $pdo->lastInsertId();
            $biblio_att_stmt->execute([
                'file_id' => $file_id,
                'biblio_id' => $biblio_id
            ]);
        }

        $success++;
    } catch (RequestException $e) {
        $failed++;
        $error = $e->getMessage();
    }

    if ($success) {
        utility::jsToastr('SIBI', "Berkas dokumen berhasil diunduh", 'success');
    }

    if ($failed) {
        utility::jsToastr('SIBI', "Berkas dokumen GAGAL diunduh karena: $error", 'error');
    }
    echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(\''.$self_url.'\');</script>';
    exit();
}

// check number of docs in sibi_docs table
$sibi_records = 0;
$stmt = $pdo->prepare('SELECT COUNT(id) as total FROM sibi_docs');
$stmt->execute();
$sibi_records = $stmt->fetchColumn();

/* search form */
?>
<div class="menuBox">
    <div class="menuBoxInner">
        <div class="per_title">
            <h2><?php echo __('SIBI'); ?></h2>
        </div>
        <div class="sub_section">
            <div class="btn-group">
                <a target="blindSubmit" href="<?= $_SERVER['PHP_SELF'] . '?' . httpQuery(['action' => 'harvest']) ?>"
                   class="btn btn-success"><?php echo __('Harvest Metadata SIBI'); ?></a>
                <?php if ($sibi_records > 0) : ?>
                    <a target="blindSubmit" href="<?= $_SERVER['PHP_SELF'] . '?' . httpQuery(['action' => 'syncbiblio']) ?>"
                    class="btn btn-primary"><?php echo __('Sync Metadata SIBI ke SLiMS'); ?></a>
                    <!-- <a target="blindSubmit" href="<?= $_SERVER['PHP_SELF'] . '?' . httpQuery(['action' => 'downloadfiles']) ?>"
                    class="btn btn-info"><?php echo __('Download Files to SLiMS'); ?></a> -->
                    <a target="blindSubmit" href="<?= $_SERVER['PHP_SELF'] . '?' . httpQuery(['action' => 'clearcache']) ?>"
                    class="btn btn-warning"><?php echo __('Bersihkan Cache SIBI'); ?></a>
                <?php endif; ?>
            </div>
            <form name="search" action="<?= $_SERVER['PHP_SELF'] . '?' . httpQuery() ?>" id="search" method="get"
                  class="form-inline"><?php echo __('Search'); ?>
                <input type="text" name="keywords" class="form-control col-md-3"/>
                <select name="type" class="form-control col-md-2">
                    <option value=""><?php echo __('Semua Jenis'); ?></option>
                    <?php foreach ($book_type as $type => $type_name) : ?>
                        <option value="<?=$type?>"><?php echo $type_name; ?> </option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" id="doSearch" value="<?php echo __('Search'); ?>" class="s-btn btn btn-default"/>
            </form>
        </div>
        <div class="infoBox">Cari data SIBI yang sudah di-harvest</div>
        <?php
        if (!file_exists( REPOBS . 'sibi_docs' )) {
            echo '<div class="errorBox">Direktori <strong>sibi_docs</strong> untuk menyimpan file dokumen 
            belum ada di bawah direktori <strong>repository</strong>. Pastikan direktori <strong>sibi_docs</strong>
            sudah ada dan mendapat hak akses tulis (<em>write</em>) oleh SLiMS.
            </div>';
        }
        ?>
    </div>
</div>
<?php
/* search form end */
// table spec
$table_spec = 'sibi_docs';
// create datagrid
$datagrid = new simbio_datagrid();

function showImage($obj_db, $array_data)
{
    $img = $array_data[1];  
    $_output = '<div class="media w-80">
          <img class="rounded" style="width:80px" loading="lazy" src="'.$img.'" alt="cover image">
        </div>';

    return $_output;
}

function splitAuthors($obj_db, $array_data)
{
    $authors_str = trim($array_data[3]);
    $authors_arr = explode(',', $authors_str);
    $authors_urled = [];
    if (count($authors_arr) > 0) {
        foreach ($authors_arr as $author) :
            $authors_urled[] = '<a href="'.$_SERVER['PHP_SELF'].'?'.httpQuery(['keywords' => $author]).'">'.$author.'</a>';
        endforeach;
    }

    return implode(', ',$authors_urled);
}

function showDownload($obj_db, $array_data)
{
    $id = $array_data[0];
    $url = $array_data[6];
    $download_url = $_SERVER['PHP_SELF'] . '?' . httpQuery(['sibi_doc_id' => $id,'action' => 'downloadfile']);
    $_output = '<a target="blindSubmit" href="'. $download_url .'" class="btn btn-info">Unduh</a>';
    
    $pdfName = urldecode( basename($url) );
    $filePath = REPOBS . 'sibi_docs/' . $pdfName;

    if (file_exists($filePath)) {
        $_output .= ' <i class="fa fa-check"></i>';
    }

    return $_output;
}

$datagrid->setSQLColumn('sibi_docs.id AS \''.__('ID').'\'',
    'sibi_docs.image AS \''.__('Image').'\'',
    'sibi_docs.title AS \''.__('Title').'\'',
    'sibi_docs.writer AS \''.__('Authors').'\'',
    'sibi_docs.level AS \''.__('Level').'\'',
    'sibi_docs.isbn AS \''.__('ISBN').'\'',
    'sibi_docs.attachment AS \''.__('File').'\'',
    'sibi_docs.category'
);

$datagrid->modifyColumnContent(1, 'callback{showImage}');
$datagrid->modifyColumnContent(3, 'callback{splitAuthors}');
$datagrid->modifyColumnContent(6, 'callback{showDownload}');
$datagrid->setSQLorder('sibi_docs.level DESC');

// is there any search
$keywords = '';
$type = '';
$search_str = '';
if (isset($_GET['keywords']) && $_GET['keywords']) {
    $keywords = utility::filterData('keywords', 'get', true, true, true);
    $searchable_fields = array('title', 'writer', 'level', 'isbn');
    foreach ($searchable_fields as $search_field) :
        $search_str .= $search_field.' LIKE \''.$keywords.'%\' OR ';
    endforeach;
    $search_str = '('.substr_replace($search_str, '', -4).')';
}
if (isset($_GET['type']) && $_GET['type']) {
    $type = utility::filterData('type', 'get', true, true, true);
    if ($keywords) {
        $search_str .= " AND (category='$type')"; 
    } else {
        $search_str .= "(category='$type')";
    }
}
if ($search_str) {
    $datagrid->setSQLcriteria($search_str);
}

// set table and table header attributes
$datagrid->table_attr = 'id="dataList" class="s-table table"';
$datagrid->table_header_attr = 'class="dataListHeader" style="font-weight: bold;"';
// set delete proccess URL
$datagrid->chbox_form_URL = $_SERVER['PHP_SELF'];
// put the result into variables
$datagrid_result = $datagrid->createDataGrid($dbs, $table_spec, 20, false);
if (isset($_GET['keywords']) AND $_GET['keywords']) {
    $msg = str_replace('{result->num_rows}', $datagrid->num_rows, __('Found <strong>{result->num_rows}</strong> from your keywords')); //mfc
    echo '<div class="infoBox">'.$msg.' : '.htmlspecialchars($_GET['keywords']).'<div>'.__('Query took').' <b>'.$datagrid->query_time.'</b> '.__('second(s) to complete').'</div></div>'; //mfc
}
echo $datagrid_result;
/* main content end */
