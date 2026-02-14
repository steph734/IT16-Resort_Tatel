<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Mockery;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymongoController;
use App\Services\PaymongoService;

class PaymentDetailsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function bookingcontroller_store_payment_session_flow_with_gcash_without_db()
    {
        // Fake storage and mail to avoid external side-effects
        Storage::fake('public');
        Mail::fake();

        // Put booking_details in session (guest + booking) using CarryOnData
        $bookingDetails = CarryOnData::bookingDetails();
        Session::put('booking_details', $bookingDetails);

        // Prepare a fake uploaded file for gcash proof
        $file = UploadedFile::fake()->image('gcash-proof.png');

        // Prepare request payload matching controller validation
        $params = [
            'payment_method' => 'gcash',
            'payment_mode' => 'account',
            'amount' => CarryOnData::bookingData(['booking' => $bookingDetails['booking']])['downpayment_amount'],
            'purpose' => 'Downpayment',
            'name_on_account' => 'Test User',
            'account_number' => '123456',
        ];

        // Build Request with file
        $request = Request::create('/store-payment', 'POST', $params, [], ['gcash_proof' => $file]);

        // Prepare mocked return objects for Guest::create, Booking::create, Payment::create
        $guestObj = (object) array_merge(CarryOnData::guest(), ['GuestID' => 'G999']);
        $bookingObj = (object) array_merge(CarryOnData::booking(), ['BookingID' => 'B999', 'GuestID' => $guestObj->GuestID]);
        $paymentObj = (object) ['PaymentID' => 'PY999'];

        // Alias-mock the models to avoid DB
        $guestMock = Mockery::mock('alias:App\\Models\\Guest');
        $guestMock->shouldReceive('create')->once()->andReturn($guestObj);

        $bookingMock = Mockery::mock('alias:App\\Models\\Booking');
        $bookingMock->shouldReceive('create')->once()->andReturn($bookingObj);

        $paymentMock = Mockery::mock('alias:App\\Models\\Payment');
        $paymentMock->shouldReceive('create')->once()->andReturn($paymentObj);

        // Call controller method
        $controller = $this->app->make(BookingController::class);
        $response = $controller->storePayment($request);

        // Controller should have stored booking_id in session for deferred flow
        $this->assertEquals('B999', Session::get('booking_id'));

        // Ensure the response is a RedirectResponse to confirmation route
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    /** @test */
    public function paymongo_create_link_deferred_flow_stores_session_and_returns_url()
    {
        // Put booking_details in session
        $bookingDetails = CarryOnData::bookingDetails();
        Session::put('booking_details', $bookingDetails);

        // Mock PaymongoService to return a checkout session
        $mock = Mockery::mock(PaymongoService::class);
        $mock->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturn([
                'checkout_url' => 'https://paymongo.test/checkout/abc123',
                'id' => 'ck_abc123',
            ]);

        // Bind the mock into container
        $this->app->instance(PaymongoService::class, $mock);

        $controller = $this->app->make(PaymongoController::class);
        $request = new Request([
            'purpose' => 'Downpayment',
            'amount' => CarryOnData::bookingData(['booking' => $bookingDetails['booking']])['downpayment_amount'],
            'agree' => '1',
        ]);

        $response = $controller->createLink($request);
        $data = $response->getData(true);

        $this->assertArrayHasKey('checkout_url', $data);
        $this->assertNotNull(Session::get('paymongo_checkout'));
    }
}
