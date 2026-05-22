<?php
require_once '../config/auth_check.php';
require_once '../config/db.php';
requireAdmin();

require_once '../libs/google-api/vendor/autoload.php';

define('SPREADSHEET_ID', '1SRVdCZU6z5-4xf_k_wYCY3VGavib2KkENLcxZDWq914');

// Fetch all inventory from DB
$result = $conn->query("
    SELECT b.name as brand, s.name as shoe_name, s.model_code, s.gender,
           s.price, i.size, i.quantity
    FROM inventory i
    JOIN shoes s ON s.id = i.shoe_id
    JOIN brands b ON b.id = s.brand_id
    ORDER BY b.name, s.name, i.size
");

$rows = [['Brand', 'Shoe Name', 'Model Code', 'Gender', 'Price', 'Size', 'Quantity']];
while ($row = $result->fetch_assoc()) {
    $rows[] = [
        $row['brand'],
        $row['shoe_name'],
        $row['model_code'] ?? '',
        ucfirst($row['gender']),
        $row['price'],
        $row['size'],
        $row['quantity'],
    ];
}

// Service account credentials embedded directly
$credentials = [
    "type" => "service_account",
    "project_id" => "shstorage-496502",
    "private_key_id" => "5b97faa73441f6dae0d7dbf8480a274806e7f63f",
    "private_key" => "-----BEGIN PRIVATE KEY-----\nMIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQDD0gA8YyfOWhWc\nkz/IwryafWnEVC4pbzlfx66RMA8gK/gAmbTNgkGcqyGeEn8/0BY/Qff/GPg1nPTv\nU3YzQqEfLekZhomwmRkMCjSUYiXnucE92xr7JA3/jUTCNBlRtIKclkZbLCNZ8ODT\nmbpkVohl3LBKgG5bkoAVyxJbNVXnUEwbIsmiGsRUA5wAi6P9xTMN4rmYrFIBatJB\n5pExMVfzZLTZDlynCtKVDZLNmusSU0U1fHR+/fVQ7CZDPR3/An3J8AEAf7FTmtEN\njM4ohWj6KFuGqF1fuJpEga8nw6klTg4CkL49czATu5PBVp9ll6KbE3ded7Axf5wj\nvi4NMrT1AgMBAAECggEAVuOf5yu4RgADBu7vpo2CUqrDe7I+qXQI6U8ZTqMckxHv\ngZSyfV4G7xWgXRuoCxCyTm9fM3pI6ME0jd1i3Wv7QnKEtCbWgd3tF0KQAidq2l+6\nN1A5sKZW78YndGTZtz95lTG5FkuHhCk2Ga5k3pDnBQvfza8fSNLAsurgwkfwTiqz\nNqTp2v4LpfXq9Tq7zXOaidLNUwOuRyzstddu4QWWlHl8IPSNGeZMl6qYcuizigM5\nb5jxNWXOSgLxoJSh2AQYvP1xvUvY5pYsKsAAdDYZF2LLqeDoVH1ZTD4s3dMtsQJd\np+focAnm5Inslt75cMiiPqtsqVOM72iklthV9knrIQKBgQD5zVZW5M83k5b+GTWj\nNbpbWPIaj81n87OflwzGNgRFB0jmkbQ/1D1Ant9BrrtD5ajNArYLUYV0bFr4ezVe\nkpYxWV6Ce20JE1RBlivbNwbC5Em8Rbd+wq3Xhw9xen+VpGT06TRlfe8moO2WTXC/\nm8u6e/HFRibE4KYaN//EnbAM1wKBgQDIrcnokEsjo+NG+EI0QvERDnmts9tztknK\nn4vKD8WM8/7s9pEh+mxTFrmeioul4gZYe0sWTOO6/wldW8zZlMumOUyy5JejXsdG\nkR46G3tKw7fcV7gEGmvl8HCKehDGWYqrAKmZ9qOY67g35k86tf0JppUGWoPaUScV\nC9CcJ5snEwKBgQDyrpcjofTiZGM7hthCMC9VEWtbfLssM2kdMQz6/31UZohfX8xC\nennrbq0szYkmpFZCnVCoXFGP0rjqUCCAOV7qoI0drLU4LOwdL7x5otLRwiEUZJKo\n9o8XJSJOt5h9k5F1EZ6Svy74Uz2eWKuhqsTY7hLq+YUunaUhMagsppljTwKBgQCl\nfVOdOqkMOhGqK/6ElJOxyisjyMd3c+MEem/P8ROremdGyMrEp3v/RSVLjds9r9gl\nzX8NY9kFE16Io7SZ1a/fYy9R81rtebKCChhQOOuCq0YLKjdxAszp8U8Nkz4UJDFL\ndZ9nfVJKQFFJn97EfuGtvLb2Z1yGhBfjrjuZGrxDCwKBgQDAiGoDbj/9xQD0hhmL\n4D5i621+hjJSIJ959shDvTDIp54qNVJ/mCZ8IMTDaha7hSadcbSJa1yhGk4DMnZt\ngbXF5yUJ1XJtxv1AQj7syONKjYI42R7zCfkdG4D+RBaiO1qC2KTwjV0v4ntku26D\nFoB7QfLh3vA/eFb5eQsKEMfmVg==\n-----END PRIVATE KEY-----\n",
    "client_email" => "shstorage-sheets@shstorage-496502.iam.gserviceaccount.com",
    "client_id" => "104619237659113196124",
    "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
    "token_uri" => "https://oauth2.googleapis.com/token",
    "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
    "client_x509_cert_url" => "https://www.googleapis.com/robot/v1/metadata/x509/shstorage-sheets%40shstorage-496502.iam.gserviceaccount.com",
    "universe_domain" => "googleapis.com"
];

// Authenticate with Google
$client = new Google\Client();
$client->setAuthConfig($credentials);
$client->addScope(Google\Service\Sheets::SPREADSHEETS);

$service = new Google\Service\Sheets($client);

// Clear existing data then write fresh
$service->spreadsheets_values->clear(
    SPREADSHEET_ID,
    'Sheet1',
    new Google\Service\Sheets\ClearValuesRequest()
);

$body = new Google\Service\Sheets\ValueRange(['values' => $rows]);
$service->spreadsheets_values->update(
    SPREADSHEET_ID,
    'Sheet1!A1',
    $body,
    ['valueInputOption' => 'RAW']
);

header("Location: ../admin/inventory.php?exported=1");
exit();