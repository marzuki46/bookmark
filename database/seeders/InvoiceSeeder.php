<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (! $user) {
            $this->command->error('No user found. Run UserSeeder first.');

            return;
        }

        $dbPath = 'C:/tool/invoice/invoice_system.db';

        if (! file_exists($dbPath)) {
            $this->command->warn('SQLite database not found at '.$dbPath.'. Skipping import.');

            return;
        }

        $sqlite = new \PDO('sqlite:'.$dbPath);
        $sqlite->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->command->info('Importing companies...');
        $companyMap = [];
        $stmt = $sqlite->query('SELECT * FROM companies');
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $logoPath = null;
            $sigPath = null;

            if (! empty($row['logo']) && str_starts_with($row['logo'], 'data:')) {
                $logoPath = $this->saveBase64($row['logo'], 'company-logos', $user->id);
            }
            if (! empty($row['signature']) && str_starts_with($row['signature'], 'data:')) {
                $sigPath = $this->saveBase64($row['signature'], 'company-signatures', $user->id);
            }

            $company = Company::create([
                'user_id' => $user->id,
                'name' => $row['name'] ?? '',
                'address' => $row['address'] ?? null,
                'email' => $row['email'] ?? null,
                'phone' => $row['phone'] ?? null,
                'bank_name' => $row['bank_name'] ?? null,
                'acc_number' => $row['acc_number'] ?? null,
                'acc_name' => $row['acc_name'] ?? null,
                'pic_name' => $row['pic_name'] ?? null,
                'logo_path' => $logoPath,
                'signature_path' => $sigPath,
            ]);

            $companyMap[$row['id']] = $company->id;
        }

        $this->command->info('Importing invoices...');
        $stmt = $sqlite->query('SELECT * FROM invoices ORDER BY id');
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $inv = Invoice::create([
                'user_id' => $user->id,
                'company_id' => $companyMap[$row['company_id']] ?? null,
                'inv_number' => $row['inv_number'] ?? '',
                'client_name' => $row['client_name'] ?? '',
                'client_address' => $row['client_address'] ?: null,
                'client_email' => $row['client_email'] ?: null,
                'date_issue' => $row['date_issue'] ?: now(),
                'date_due' => $row['date_due'] ?: null,
                'status' => $row['status'] ?? 'unpaid',
                'work_status' => $row['work_status'] ?? 'on_progress',
                'internal_deadline' => $row['internal_deadline'] ?: null,
                'tax_rate' => $row['tax_rate'] ?? 0,
                'tax_amount' => $row['tax_amount'] ?? 0,
                'grand_total' => $row['grand_total'] ?? 0,
            ]);

            $itemStmt = $sqlite->prepare('SELECT * FROM invoice_items WHERE invoice_id = ?');
            $itemStmt->execute([$row['id']]);
            while ($item = $itemStmt->fetch(\PDO::FETCH_ASSOC)) {
                InvoiceItem::create([
                    'invoice_id' => $inv->id,
                    'description' => $item['description'] ?? '',
                    'qty' => $item['qty'] ?? 1,
                    'price' => $item['price'] ?? 0,
                    'total' => $item['total'] ?? 0,
                ]);
            }

            $payStmt = $sqlite->prepare('SELECT * FROM payments WHERE invoice_id = ?');
            $payStmt->execute([$row['id']]);
            while ($pay = $payStmt->fetch(\PDO::FETCH_ASSOC)) {
                Payment::create([
                    'invoice_id' => $inv->id,
                    'amount' => $pay['amount'] ?? 0,
                    'payment_date' => $pay['payment_date'] ?: now(),
                    'note' => $pay['note'] ?: null,
                ]);
            }
        }

        $this->command->info('Importing bills...');
        $stmt = $sqlite->query('SELECT * FROM bills ORDER BY id');
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            Bill::create([
                'user_id' => $user->id,
                'description' => $row['description'] ?? '',
                'amount' => $row['amount'] ?? 0,
                'due_date' => $row['due_date'] ?: now(),
                'status' => $row['status'] ?? 'unpaid',
                'category' => $row['category'] ?? 'Lainnya',
            ]);
        }

        $this->command->info('Invoice data imported successfully!');
    }

    private function saveBase64(string $dataUri, string $folder, int $userId): ?string
    {
        try {
            [$meta, $data] = explode(';', $dataUri, 2);
            [, $base64] = explode(',', $data);
            $ext = match (true) {
                str_contains($meta, 'png') => 'png',
                str_contains($meta, 'jpeg'), str_contains($meta, 'jpg') => 'jpg',
                str_contains($meta, 'gif') => 'gif',
                default => 'png',
            };
            $filename = $folder.'/'.$userId.'_'.time().'.'.$ext;
            $path = storage_path('app/public/'.$filename);
            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }
            file_put_contents($path, base64_decode($base64));

            return $filename;
        } catch (\Exception $e) {
            return null;
        }
    }
}
