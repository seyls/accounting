<?php

namespace Tests\Unit;


use Illuminate\Support\Facades\Auth;

use Carbon\Carbon;

use Seyls\Accounting\Tests\TestCase;

use Seyls\Accounting\User;

use Seyls\Accounting\Models\RecycledObject;
use Seyls\Accounting\Models\ReportingPeriod;
use Seyls\Accounting\Models\Account;
use Seyls\Accounting\Models\Assignment;
use Seyls\Accounting\Models\ClosingRate;
use Seyls\Accounting\Models\Currency;
use Seyls\Accounting\Models\ExchangeRate;
use Seyls\Accounting\Models\LineItem;
use Seyls\Accounting\Models\Vat;

use Seyls\Accounting\Transactions\ClientInvoice;
use Seyls\Accounting\Transactions\ClientReceipt;
use Seyls\Accounting\Transactions\SupplierBill;
use Seyls\Accounting\Transactions\SupplierPayment;

use Seyls\Accounting\Exceptions\InvalidAccountType;
use Seyls\Accounting\Exceptions\InvalidPeriodStatus;
use Seyls\Accounting\Exceptions\MissingClosingRate;
use Seyls\Accounting\Exceptions\MissingReportingPeriod;

class ReportingPeriodTest extends TestCase
{
    /**
     * ReportingPeriod Model relationships test.
     *
     * @return void
     */
    public function testReportingPeriodRelationships()
    {
        $entity = Auth::user()->entity;

        $period = new ReportingPeriod([
            'period_count' => 1,
            'calendar_year' => Carbon::now()->year,
        ]);
        $period->save();

        $period->attributes();
        $this->assertEquals($entity->reportingPeriods->last()->calendar_year, $period->calendar_year);
        $this->assertEquals(
            $period->toString(true),
            'ReportingPeriod: ' . $period->calendar_year
        );
        $this->assertEquals(
            $period->toString(),
            $period->calendar_year
        );
    }

    /**
     * Test ReportingPeriod model Entity Scope.
     *
     * @return void
     */
    public function testReportingPeriodEntityScope()
    {
        $user = factory(User::class)->create();
        $user->entity_id = 2;
        $user->save();

        $this->be($user);

        $this->assertEquals(count(ReportingPeriod::all()), 0);

        $this->be(User::withoutGlobalScopes()->find(1));
        $this->assertEquals(count(ReportingPeriod::all()), 1);
    }

    /**
     * Test ReportingPeriod Model recylcling
     *
     * @return void
     */
    public function testReportingPeriodRecycling()
    {
        $period = new ReportingPeriod([
            'period_count' => 1,
            'calendar_year' => Carbon::now()->year,
        ]);

        $period->delete();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($period->recycled->first(), $recycled);
    }

    /**
     * Test ReportingPeriod Dates
     *
     * @return void
     */
    public function testReportingPeriodDates()
    {
        $this->assertEquals(ReportingPeriod::year(), date("Y"));
        $this->assertEquals(ReportingPeriod::year("2025-06-25"), "2025");
        $this->assertEquals(ReportingPeriod::periodStart("2025-06-25")->toDateString(), "2025-01-01");

        $entity = Auth::user()->entity;
        $entity->year_start = 4;
        $entity->save();

        $this->assertEquals(ReportingPeriod::year("2025-03-25"), "2024");
        $this->assertEquals(ReportingPeriod::periodStart("2025-03-25")->toDateString(), "2024-04-01");
        $this->assertEquals(ReportingPeriod::periodEnd("2025-03-25")->toDateString(), "2025-03-31");
    }

    /**
     * Test Missing Report Period.
     *
     * @return void
     */
    public function testMissingReportPeriod()
    {
        $this->expectException(MissingReportingPeriod::class);
        $this->expectExceptionMessage('has no reporting period defined for the year');

        ReportingPeriod::getPeriod(Carbon::parse("1970-01-01"));
    }

    /**
     * Test Transaction Curencies.
     *
     * @return void
     */
    public function testTransactionCurencies()
    {        
        $currency1 = factory(Currency::class)->create();

         // Receivables
         $account1 = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null,
            'currency_id' => $currency1->id,
        ]);

        $transaction = new ClientReceipt([
            "account_id" => $account1->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                'currency_id' => $currency1->id,
                "rate" => 110
            ])->id,
            'currency_id' => $currency1->id,
        ]);
        
        
        $line = new LineItem([
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency1->id,
            ])->id,
            'amount' => 100,
        ]);

        
        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = new ClientInvoice([
            "account_id" => $account1->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                'currency_id' => $currency1->id,
                "rate" => 100
            ])->id,
            'currency_id' => $currency1->id,
        ]);

        $line = new LineItem([
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::OPERATING_REVENUE,
                'category_id' => null
            ])->id,
            'amount' => 100,
        ]);

        $cleared->addLineItem($line);
        $cleared->post();
        
        $forex = factory(Account::class)->create([
            'account_type' => Account::NON_OPERATING_REVENUE,
            'category_id' => null
        ]);

        $assignment = new Assignment([
            'assignment_date' => Carbon::now(),
            'transaction_id' => $transaction->id,
            'cleared_id' => $cleared->id,
            'cleared_type' => $cleared->cleared_type,
            'amount' => 100,
            'forex_account_id' => $forex->id,
        ]);
        $assignment->save();

        $this->assertEquals($forex->Closingbalance(), [$this->reportingCurrencyId => -1000]);

        $currency2 = factory(Currency::class)->create();

        // Payables
        $account2 = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
            'category_id' => null,
            'currency_id' => $currency2->id,
        ]);

        $transaction = new SupplierPayment([
            "account_id" => $account2->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 100,
                'currency_id' => $currency2->id,
            ])->id,
            'currency_id' => $currency2->id,
        ]);
        
        $line = new LineItem([
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency2->id,
            ])->id,
            'amount' => 50,
        ]);
        
        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = new SupplierBill([
            "account_id" => $account2->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 110,
                'currency_id' => $currency2->id,
            ])->id,
            'currency_id' => $currency2->id,
        ]);

        $line = new LineItem([
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::NON_CURRENT_ASSET,
                'category_id' => null
            ])->id,
            'amount' => 100,
        ]);

        $cleared->addLineItem($line);
        $cleared->post();

        $currencies = $this->period->transactionCurrencies();
        $this->assertEquals(count($currencies), 2);
        $this->assertEquals($currencies[0]->currency_id, $currency1->id);
        $this->assertEquals($currencies[1]->currency_id, $currency2->id);

        $currencies = $this->period->transactionCurrencies($account1->id);

        $this->assertEquals(count($currencies), 1);
        $this->assertEquals($currencies[0]->currency_id, $currency1->id);

        $currencies = $this->period->transactionCurrencies($account2->id);

        $this->assertEquals(count($currencies), 1);
        $this->assertEquals($currencies[0]->currency_id, $currency2->id);
    }

    /**
     * Test Invalid Account Type.
     *
     * @return void
     */
    public function testInvalidAccountType()
    {
        $forex = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null
        ]);

        $this->expectException(InvalidAccountType::class);
        $this->expectExceptionMessage('Transaltion Forex Account must be of Type Equity');

        $this->period->prepareBalancesTranslation($forex->id);
        
    }

    /**
     * Test Invalid Period Status.
     *
     * @return void
     */
    public function testPeriodStatus()
    {
        $forex = factory(Account::class)->create([
            'account_type' => Account::EQUITY,
            'category_id' => null
        ]);

        $this->expectException(InvalidPeriodStatus::class);
        $this->expectExceptionMessage('Reporting Period must have Adjusting status to translate foreign balances');

        $this->period->prepareBalancesTranslation($forex->id);
        
    }

    /**
     * Test Missing Closing Rate.
     *
     * @return void
     */
    public function testMissingClosingRate()
    {
        $forex = factory(Account::class)->create([
            'account_type' => Account::EQUITY,
            'category_id' => null
        ]);

        $this->period->status = ReportingPeriod::ADJUSTING;

        $this->assertEquals($this->period->prepareBalancesTranslation($forex->id), []);

        $currency1 = factory(Currency::class)->create();
        
        // Receivables
        $account1 = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null,
            'currency_id' => $currency1->id,
        ]);

        $transaction = new ClientReceipt([
            "account_id" => $account1->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 110,
                'currency_id' => $currency1->id,
            ])->id,
            'currency_id' => $currency1->id,
        ]);
        
        $line = new LineItem([
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency1->id,
            ])->id,
            'amount' => 100,
        ]);
        
        $transaction->addLineItem($line);
        $transaction->post();

        $this->expectException(MissingClosingRate::class);
        $this->expectExceptionMessage('Closing Rate for ' . $currency1->currency_code . ' is missing ');

        $this->period->prepareBalancesTranslation($forex->id);
    }

    /**
     * Test Balances Translation.
     *
     * @return void
     */
    public function testBalancesTranslation()
    {
        $forex = factory(Account::class)->create([
            'account_type' => Account::EQUITY,
            'category_id' => null
        ]);

        $currency1 = factory(Currency::class)->create();
        ClosingRate::create([
            'exchange_rate_id' => factory(ExchangeRate::class)->create([
                'currency_id' => $currency1->id,
                "rate" => 100,
            ])->id,
            'reporting_period_id' => $this->period->id,
        ]);

        // Receivables
        $account1 = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null,
            'currency_id' => $currency1->id,
        ]);
        $account2 = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null,
            'currency_id' => $currency1->id,
        ]);

        $transaction = new ClientReceipt([
            "account_id" => $account1->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 110,
                'currency_id' => $currency1->id,
            ])->id,
            'currency_id' => $currency1->id,
        ]);
        
        $line = new LineItem([
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency1->id,
            ])->id,
            'amount' => 100,
        ]);
        
        $transaction->addLineItem($line);
        $transaction->post();

        $transaction = new ClientReceipt([
            "account_id" => $account2->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 90,
                'currency_id' => $currency1->id,
            ])->id,
            'currency_id' => $currency1->id,
        ]);
        
        $line = new LineItem([
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency1->id,
            ])->id,
            'amount' => 100,
        ]);
        
        $transaction->addLineItem($line);
        $transaction->post();

        $this->period->status = ReportingPeriod::ADJUSTING;

        $transactions = $this->period->prepareBalancesTranslation($forex->id);

        $this->assertEquals($transactions[0]->account->id, $account1->id);
        $this->assertTrue($transactions[0]->credited);
        $this->assertEquals($transactions[0]->narration, $currency1->currency_code . " ". $this->period->calendar_year . " Forex Balance Translation");
        $this->assertEquals($transactions[0]->amount, 1000);
        $this->assertFalse($transactions[0]->is_posted);

        $this->assertEquals($transactions[1]->account->id, $account2->id);
        $this->assertFalse($transactions[1]->credited);
        $this->assertEquals($transactions[1]->narration, $currency1->currency_code . " ". $this->period->calendar_year . " Forex Balance Translation");
        $this->assertEquals($transactions[1]->amount, 1000);
        $this->assertFalse($transactions[1]->is_posted);

        // Payables
        $account3 = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
            'category_id' => null,
            'currency_id' => $currency1->id,
        ]);
        $account4 = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
            'category_id' => null,
            'currency_id' => $currency1->id,
        ]);

        $transaction = new SupplierBill([
            "account_id" => $account3->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 110,
                'currency_id' => $currency1->id,
            ])->id,
            'currency_id' => $currency1->id,
        ]);
        
        $line = new LineItem([
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::NON_CURRENT_ASSET,
                'category_id' => null,
                'currency_id' => $currency1->id,
            ])->id,
            'amount' => 100,
        ]);
        
        $transaction->addLineItem($line);
        $transaction->post();

        $transaction = new SupplierBill([
            "account_id" => $account4->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 90,
                'currency_id' => $currency1->id,
            ])->id,
            'currency_id' => $currency1->id,
        ]);
        
        $line = new LineItem([
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::NON_CURRENT_ASSET,
                'category_id' => null,
                'currency_id' => $currency1->id,
            ])->id,
            'amount' => 100,
        ]);
        
        $transaction->addLineItem($line);
        $transaction->post();

        $transactions = $this->period->prepareBalancesTranslation($forex->id);

        $this->assertEquals($transactions[0]->account->id, $account3->id);
        $this->assertFalse($transactions[0]->credited);
        $this->assertEquals($transactions[0]->narration, $currency1->currency_code . " ". $this->period->calendar_year . " Forex Balance Translation");
        $this->assertEquals($transactions[0]->amount, 1000);
        $this->assertFalse($transactions[0]->is_posted);

        $this->assertEquals($transactions[1]->account->id, $account4->id);
        $this->assertTrue($transactions[1]->credited);
        $this->assertEquals($transactions[1]->narration, $currency1->currency_code . " ". $this->period->calendar_year . " Forex Balance Translation");
        $this->assertEquals($transactions[1]->amount, 1000);
        $this->assertFalse($transactions[1]->is_posted);
    }

    /**
     * Test Closing Transactions.
     *
     * @return void
     */
    public function testClosingTransactions()
    {
        $forex = factory(Account::class)->create([
            'account_type' => Account::EQUITY,
            'category_id' => null
        ]);

        $currency1 = factory(Currency::class)->create();
        $closingRate1 = ClosingRate::create([
            'exchange_rate_id' => factory(ExchangeRate::class)->create([
                'currency_id' => $currency1->id,
                "rate" => 100,
            ])->id,
            'reporting_period_id' => $this->period->id,
        ]);

        // Receivables
        $account1 = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null,
            'currency_id' => $currency1->id,
        ]);
        $account2 = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null,
            'currency_id' => $currency1->id,
        ]);

        $transaction = new ClientReceipt([
            "account_id" => $account1->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 110,
                'currency_id' => $currency1->id,
            ])->id,
            'currency_id' => $currency1->id,
        ]);
        
        $line = new LineItem([
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency1->id,
            ])->id,
            'amount' => 100,
        ]);
        
        $transaction->addLineItem($line);
        $transaction->post();

        $transaction = new ClientReceipt([
            "account_id" => $account2->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 90,
                'currency_id' => $currency1->id,
            ])->id,
            'currency_id' => $currency1->id,
        ]);
        
        $line = new LineItem([
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency1->id,
            ])->id,
            'amount' => 100,
        ]);
        
        $transaction->addLineItem($line);
        $transaction->post();

        // Payables
        $account3 = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
            'category_id' => null,
            'currency_id' => $currency1->id,
        ]);
        $account4 = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
            'category_id' => null,
            'currency_id' => $currency1->id,
        ]);

        $transaction = new SupplierBill([
            "account_id" => $account3->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 110,
                'currency_id' => $currency1->id,
            ])->id,
            'currency_id' => $currency1->id,
        ]);
        
        $line = new LineItem([
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::NON_CURRENT_ASSET,
                'category_id' => null,
                'currency_id' => $currency1->id,
            ])->id,
            'amount' => 100,
        ]);
        
        $transaction->addLineItem($line);
        $transaction->post();

        $transaction = new SupplierBill([
            "account_id" => $account4->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 90,
                'currency_id' => $currency1->id,
            ])->id,
            'currency_id' => $currency1->id,
        ]);
        
        $line = new LineItem([
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::NON_CURRENT_ASSET,
                'category_id' => null,
                'currency_id' => $currency1->id,
            ])->id,
            'amount' => 100,
        ]);
        
        $transaction->addLineItem($line);
        $transaction->post();

        $currency2 = factory(Currency::class)->create();

        $closingRate2 = ClosingRate::create([
            'exchange_rate_id' => factory(ExchangeRate::class)->create([
                'currency_id' => $currency2->id,
                "rate" => 100,
            ])->id,
            'reporting_period_id' => $this->period->id,
        ]);

        $transaction = new ClientReceipt([
            "account_id" => $account1->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 90,
                'currency_id' => $currency2->id,
            ])->id,
            'currency_id' => $currency2->id,
        ]);
        
        $line = new LineItem([
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency2->id,
            ])->id,
            'amount' => 100,
        ]);
        
        $transaction->addLineItem($line);
        $transaction->post();

        $this->period->status = ReportingPeriod::ADJUSTING;
        
        $this->period->prepareBalancesTranslation($forex->id);

        $transactions = $this->period->getTranslations();

        $this->assertEquals(
            $transactions[$account1->name],[
                [
                    "currency" => $currency1->currency_code,
                    "closingRate" => $closingRate1->exchangeRate->rate,
                    "currencyBalance" => -100,
                    "localBalance" => -11000,
                    "foreignBalance" => -10000,
                    "translation" => -1000.0,
                    "posted" => false
                ],
                [
                    "currency" => $currency2->currency_code,
                    "closingRate" => $closingRate2->exchangeRate->rate,
                    "currencyBalance" => -100,
                    "localBalance" => -9000,
                    "foreignBalance" => -10000,
                    "translation" => 1000.0,
                    "posted" => false
                ],
            ] 
        );
        $this->assertEquals(
            $transactions[$account2->name],[
                [
                    "currency" => $currency1->currency_code,
                    "closingRate" => $closingRate1->exchangeRate->rate,
                    "currencyBalance" => -100,
                    "localBalance" => -9000,
                    "foreignBalance" => -10000,
                    "translation" => 1000.0,
                    "posted" => false
                ],
            ] 
        );
        $this->assertEquals(
            $transactions[$account3->name],[
                [
                    "currency" => $currency1->currency_code,
                    "closingRate" => $closingRate1->exchangeRate->rate,
                    "currencyBalance" => -100,
                    "localBalance" => -11000,
                    "foreignBalance" => -10000,
                    "translation" => 1000.0,
                    "posted" => false
                ],
            ] 
        );
        $this->assertEquals(
            $transactions[$account4->name],[
                [
                    "currency" => $currency1->currency_code,
                    "closingRate" => $closingRate1->exchangeRate->rate,
                    "currencyBalance" => -100,
                    "localBalance" => -9000,
                    "foreignBalance" => -10000,
                    "translation" => -1000.0,
                    "posted" => false
                ],
            ] 
        );

        $this->period->postTranslations();

        $transactions = $this->period->getTranslations();

        $this->assertEquals(
            $transactions[$account1->name],[
                [
                    "currency" => $currency1->currency_code,
                    "closingRate" => $closingRate1->exchangeRate->rate,
                    "currencyBalance" => -100,
                    "localBalance" => -11000,
                    "foreignBalance" => -10000,
                    "translation" => -1000.0,
                    "posted" => true
                ],
                [
                    "currency" => $currency2->currency_code,
                    "closingRate" => $closingRate1->exchangeRate->rate,
                    "currencyBalance" => -100,
                    "localBalance" => -9000,
                    "foreignBalance" => -10000,
                    "translation" => 1000.0,
                    "posted" => true
                ],
            ] 
        );
        $this->assertEquals(
            $transactions[$account2->name],[
                [
                    "currency" => $currency1->currency_code,
                    "closingRate" => $closingRate1->exchangeRate->rate,
                    "currencyBalance" => -100,
                    "localBalance" => -9000,
                    "foreignBalance" => -10000,
                    "translation" => 1000.0,
                    "posted" => true
                ],
            ] 
        );
        $this->assertEquals(
            $transactions[$account3->name],[
                [
                    "currency" => $currency1->currency_code,
                    "closingRate" => $closingRate1->exchangeRate->rate,
                    "currencyBalance" => -100,
                    "localBalance" => -11000,
                    "foreignBalance" => -10000,
                    "translation" => 1000.0,
                    "posted" => true
                ],
            ] 
        );
        $this->assertEquals(
            $transactions[$account4->name],[
                [
                    "currency" => $currency1->currency_code,
                    "closingRate" => $closingRate1->exchangeRate->rate,
                    "currencyBalance" => -100,
                    "localBalance" => -9000,
                    "foreignBalance" => -10000,
                    "translation" => -1000.0,
                    "posted" => true
                ],
            ] 
        );
    }
}
