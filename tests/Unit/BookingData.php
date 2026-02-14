<?php

namespace Tests\Unit;

use Carbon\Carbon;

/**
 * Reusable OOP data-holder classes for unit tests.
 * Each class exposes static make($overrides = []) and ->toArray()
 * so tests can call Model::create(BookingData::make()->toArray()) or
 * use the arrays directly: BookingData::makeArray($overrides)
 */

class GuestData
{
    private static int $counter = 1;

    public string $GuestID;
    public string $FName;
    public ?string $MName;
    public string $LName;
    public string $Email;
    public string $Phone;
    public ?string $Address;
    public bool $Contactable;

    public function __construct(array $overrides = [])
    {
        $this->GuestID = $overrides['GuestID'] ?? self::generateGuestID();
        $this->FName = $overrides['FName'] ?? 'Test';
        $this->MName = $overrides['MName'] ?? null;
        $this->LName = $overrides['LName'] ?? 'Guest';
        $this->Email = $overrides['Email'] ?? 'test.guest@example.com';
        $this->Phone = $overrides['Phone'] ?? '09171234567';
        $this->Address = $overrides['Address'] ?? '123 Test St';
        $this->Contactable = $overrides['Contactable'] ?? true;
    }

    public static function generateGuestID(): string
    {
        return 'G' . str_pad(self::$counter++, 3, '0', STR_PAD_LEFT);
    }

    public static function make(array $overrides = []): self
    {
        return new self($overrides);
    }

    public static function makeArray(array $overrides = []): array
    {
        return self::make($overrides)->toArray();
    }

    public function toArray(): array
    {
        return [
            'GuestID' => $this->GuestID,
            'FName' => $this->FName,
            'MName' => $this->MName,
            'LName' => $this->LName,
            'Email' => $this->Email,
            'Phone' => $this->Phone,
            'Address' => $this->Address,
            'Contactable' => $this->Contactable,
        ];
    }
}

class PackageData
{
    private static int $counter = 1;

    public string $PackageID;
    public string $Name;
    public ?string $Description;
    public float $Price;
    public int $max_guests;
    public float $excess_rate;

    public function __construct(array $overrides = [])
    {
        $this->PackageID = $overrides['PackageID'] ?? self::generatePackageID();
        $this->Name = $overrides['Name'] ?? 'Standard Package';
        $this->Description = $overrides['Description'] ?? json_encode(['Pool', 'Breakfast']);
        $this->Price = $overrides['Price'] ?? 2500.00;
        $this->max_guests = $overrides['max_guests'] ?? 4;
        $this->excess_rate = $overrides['excess_rate'] ?? 500.00;
    }

    public static function generatePackageID(): string
    {
        return 'PK' . str_pad(self::$counter++, 3, '0', STR_PAD_LEFT);
    }

    public static function make(array $overrides = []): self
    {
        return new self($overrides);
    }

    public static function makeArray(array $overrides = []): array
    {
        return self::make($overrides)->toArray();
    }

    public function toArray(): array
    {
        return [
            'PackageID' => $this->PackageID,
            'Name' => $this->Name,
            'Description' => $this->Description,
            'Price' => $this->Price,
            'max_guests' => $this->max_guests,
            'excess_rate' => $this->excess_rate,
        ];
    }
}

class PaymentData
{
    private static int $counter = 1;

    public string $PaymentID;
    public ?string $BookingID;
    public string $PaymentDate;
    public float $Amount;
    public float $TotalAmount;
    public ?string $PaymentMethod;
    public ?string $PaymentStatus;
    public ?string $PaymentPurpose;
    public ?string $ReferenceNumber;
    public ?string $NameOnAccount;
    public ?string $AccountNumber;

    public function __construct(array $overrides = [])
    {
        $this->PaymentID = $overrides['PaymentID'] ?? self::generatePaymentID();
        $this->BookingID = $overrides['BookingID'] ?? null;
        $this->PaymentDate = $overrides['PaymentDate'] ?? Carbon::now()->toDateTimeString();
        $this->Amount = $overrides['Amount'] ?? 1000.00;
        $this->TotalAmount = $overrides['TotalAmount'] ?? $this->Amount;
        $this->PaymentMethod = $overrides['PaymentMethod'] ?? 'Cash';
        $this->PaymentStatus = $overrides['PaymentStatus'] ?? 'For Verification';
        $this->PaymentPurpose = $overrides['PaymentPurpose'] ?? 'Booking Payment';
        $this->ReferenceNumber = $overrides['ReferenceNumber'] ?? null;
        $this->NameOnAccount = $overrides['NameOnAccount'] ?? null;
        $this->AccountNumber = $overrides['AccountNumber'] ?? null;
    }

    public static function generatePaymentID(): string
    {
        return 'PY' . str_pad(self::$counter++, 3, '0', STR_PAD_LEFT);
    }

    public static function make(array $overrides = []): self
    {
        return new self($overrides);
    }

    public static function makeArray(array $overrides = []): array
    {
        return self::make($overrides)->toArray();
    }

    public function toArray(): array
    {
        return [
            'PaymentID' => $this->PaymentID,
            'BookingID' => $this->BookingID,
            'PaymentDate' => $this->PaymentDate,
            'Amount' => $this->Amount,
            'TotalAmount' => $this->TotalAmount,
            'PaymentMethod' => $this->PaymentMethod,
            'PaymentStatus' => $this->PaymentStatus,
            'PaymentPurpose' => $this->PaymentPurpose,
            'ReferenceNumber' => $this->ReferenceNumber,
            'NameOnAccount' => $this->NameOnAccount,
            'AccountNumber' => $this->AccountNumber,
        ];
    }
}

class BookingData
{
    private static int $counter = 1;

    public string $BookingID;
    public string $GuestID;
    public string $PackageID;
    public string $BookingDate;
    public string $CheckInDate;
    public string $CheckOutDate;
    public ?string $ActualCheckInTime;
    public ?string $ActualCheckOutTime;
    public string $BookingStatus;
    public int $Pax;
    public int $NumOfChild;
    public int $NumOfAdults;
    public float $ExcessFee;

    public function __construct(array $overrides = [])
    {
        $this->BookingID = $overrides['BookingID'] ?? self::generateBookingID();
        $this->GuestID = $overrides['GuestID'] ?? GuestData::make()->GuestID;
        $this->PackageID = $overrides['PackageID'] ?? PackageData::make()->PackageID;
        $now = Carbon::now();
        $this->BookingDate = $overrides['BookingDate'] ?? $now->toDateTimeString();
        $this->CheckInDate = $overrides['CheckInDate'] ?? $now->copy()->addDays(7)->toDateTimeString();
        $this->CheckOutDate = $overrides['CheckOutDate'] ?? $now->copy()->addDays(9)->toDateTimeString();
        $this->ActualCheckInTime = $overrides['ActualCheckInTime'] ?? null;
        $this->ActualCheckOutTime = $overrides['ActualCheckOutTime'] ?? null;
        $this->BookingStatus = $overrides['BookingStatus'] ?? 'Confirmed';
        $this->Pax = $overrides['Pax'] ?? 2;
        $this->NumOfChild = $overrides['NumOfChild'] ?? 0;
        $this->NumOfAdults = $overrides['NumOfAdults'] ?? 2;
        $this->ExcessFee = $overrides['ExcessFee'] ?? 0.00;
    }

    public static function generateBookingID(): string
    {
        return 'B' . str_pad(self::$counter++, 3, '0', STR_PAD_LEFT);
    }

    public static function make(array $overrides = []): self
    {
        return new self($overrides);
    }

    public static function makeArray(array $overrides = []): array
    {
        return self::make($overrides)->toArray();
    }

    public function toArray(): array
    {
        return [
            'BookingID' => $this->BookingID,
            'GuestID' => $this->GuestID,
            'PackageID' => $this->PackageID,
            'BookingDate' => $this->BookingDate,
            'CheckInDate' => $this->CheckInDate,
            'CheckOutDate' => $this->CheckOutDate,
            'ActualCheckInTime' => $this->ActualCheckInTime,
            'ActualCheckOutTime' => $this->ActualCheckOutTime,
            'BookingStatus' => $this->BookingStatus,
            'Pax' => $this->Pax,
            'NumOfChild' => $this->NumOfChild,
            'NumOfAdults' => $this->NumOfAdults,
            'ExcessFee' => $this->ExcessFee,
        ];
    }
}

class InventoryItemData
{
    private static int $counter = 1;

    public string $name;
    public string $category;
    public ?string $sub_category;
    public ?string $description;
    public int $quantity_on_hand;
    public int $reorder_level;
    public float $average_cost;
    public ?string $unit_of_measure;
    public ?string $sku;
    public ?string $location;
    public bool $is_active;
    public ?int $rental_item_id;

    public function __construct(array $overrides = [])
    {
        $this->name = $overrides['name'] ?? 'Test Item ' . self::$counter;
        $this->category = $overrides['category'] ?? 'resort_supply';
        $this->sub_category = $overrides['sub_category'] ?? null;
        $this->description = $overrides['description'] ?? 'Sample inventory item';
        $this->quantity_on_hand = $overrides['quantity_on_hand'] ?? 10;
        $this->reorder_level = $overrides['reorder_level'] ?? 2;
        $this->average_cost = $overrides['average_cost'] ?? 150.00;
        $this->unit_of_measure = $overrides['unit_of_measure'] ?? 'pcs';
        $this->sku = $overrides['sku'] ?? 'SKU' . str_pad(self::$counter, 4, '0', STR_PAD_LEFT);
        $this->location = $overrides['location'] ?? 'Warehouse';
        $this->is_active = $overrides['is_active'] ?? true;
        $this->rental_item_id = $overrides['rental_item_id'] ?? null;
        self::$counter++;
    }

    public static function make(array $overrides = []): self
    {
        return new self($overrides);
    }

    public static function makeArray(array $overrides = []): array
    {
        return self::make($overrides)->toArray();
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'category' => $this->category,
            'sub_category' => $this->sub_category,
            'description' => $this->description,
            'quantity_on_hand' => $this->quantity_on_hand,
            'reorder_level' => $this->reorder_level,
            'average_cost' => $this->average_cost,
            'unit_of_measure' => $this->unit_of_measure,
            'sku' => $this->sku,
            'location' => $this->location,
            'is_active' => $this->is_active,
            'rental_item_id' => $this->rental_item_id,
        ];
    }
}

class RentalItemData
{
    private static int $counter = 1;

    public string $name;
    public string $code;
    public string $rate_type;
    public float $rate;
    public int $stock_on_hand;
    public ?string $description;
    public string $status;

    public function __construct(array $overrides = [])
    {
        $this->name = $overrides['name'] ?? 'Rental Item ' . self::$counter;
        $this->code = $overrides['code'] ?? 'RNT' . str_pad(self::$counter, 3, '0', STR_PAD_LEFT);
        $this->rate_type = $overrides['rate_type'] ?? 'Per-Day';
        $this->rate = $overrides['rate'] ?? 100.00;
        $this->stock_on_hand = $overrides['stock_on_hand'] ?? 5;
        $this->description = $overrides['description'] ?? null;
        $this->status = $overrides['status'] ?? 'Active';
        self::$counter++;
    }

    public static function make(array $overrides = []): self
    {
        return new self($overrides);
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'code' => $this->code,
            'rate_type' => $this->rate_type,
            'rate' => $this->rate,
            'stock_on_hand' => $this->stock_on_hand,
            'description' => $this->description,
            'status' => $this->status,
        ];
    }
}

class RentalData
{
    public ?string $BookingID;
    public ?int $rental_item_id;
    public int $quantity;
    public float $rate_snapshot;
    public string $rate_type_snapshot;
    public string $status;
    public int $returned_quantity;
    public ?string $issued_at;
    public ?string $returned_at;

    public function __construct(array $overrides = [])
    {
        $this->BookingID = $overrides['BookingID'] ?? null;
        $this->rental_item_id = $overrides['rental_item_id'] ?? null;
        $this->quantity = $overrides['quantity'] ?? 1;
        $this->rate_snapshot = $overrides['rate_snapshot'] ?? 100.00;
        $this->rate_type_snapshot = $overrides['rate_type_snapshot'] ?? 'Per-Day';
        $this->status = $overrides['status'] ?? 'Issued';
        $this->returned_quantity = $overrides['returned_quantity'] ?? 0;
        $this->issued_at = $overrides['issued_at'] ?? Carbon::now()->toDateTimeString();
        $this->returned_at = $overrides['returned_at'] ?? null;
    }

    public static function make(array $overrides = []): self
    {
        return new self($overrides);
    }

    public function toArray(): array
    {
        return [
            'BookingID' => $this->BookingID,
            'rental_item_id' => $this->rental_item_id,
            'quantity' => $this->quantity,
            'rate_snapshot' => $this->rate_snapshot,
            'rate_type_snapshot' => $this->rate_type_snapshot,
            'status' => $this->status,
            'returned_quantity' => $this->returned_quantity,
            'issued_at' => $this->issued_at,
            'returned_at' => $this->returned_at,
        ];
    }
}

class UnpaidItemData
{
    private static int $counter = 1;

    public string $ItemID;
    public ?string $BookingID;
    public string $ItemName;
    public int $Quantity;
    public float $Price;
    public float $TotalAmount;
    public bool $IsPaid;

    public function __construct(array $overrides = [])
    {
        $this->ItemID = $overrides['ItemID'] ?? $this->generateItemID();
        $this->BookingID = $overrides['BookingID'] ?? null;
        $this->ItemName = $overrides['ItemName'] ?? 'Extra Service';
        $this->Quantity = $overrides['Quantity'] ?? 1;
        $this->Price = $overrides['Price'] ?? 500.00;
        $this->TotalAmount = $this->Quantity * $this->Price;
        $this->IsPaid = $overrides['IsPaid'] ?? false;
    }

    private function generateItemID(): string
    {
        return 'UI' . str_pad(self::$counter++, 3, '0', STR_PAD_LEFT);
    }

    public static function make(array $overrides = []): self
    {
        return new self($overrides);
    }

    public function toArray(): array
    {
        return [
            'ItemID' => $this->ItemID,
            'BookingID' => $this->BookingID,
            'ItemName' => $this->ItemName,
            'Quantity' => $this->Quantity,
            'Price' => $this->Price,
            'TotalAmount' => $this->TotalAmount,
            'IsPaid' => $this->IsPaid,
        ];
    }
}

