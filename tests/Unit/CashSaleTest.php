<?php

namespace Tests\Unit;

use Carbon\Carbon;

use Seyls\Accounting\Tests\TestCase;

use Seyls\Accounting\Models\Account;
use Seyls\Accounting\Models\Balance;
use Seyls\Accounting\Models\Currency;
use Seyls\Accounting\Models\Ledger;
use Seyls\Accounting\Models\LineItem;
use Seyls\Accounting\Models\Vat;

use Seyls\Accounting\Transactions\CashSale;

use Seyls\Accounting\Exceptions\LineItemAccount;
use Seyls\Accounting\Exceptions\MainAccount;

class CashSaleTest extends TestCase
{
    /**
     * Test Creating CashSale Transaction
     *
     * @return void
     */
    public function testCreateCashSaleTransaction()
    {
        $bankAccount = factory(Account::class)->create([
            'account_type' => Account::BANK,
            'category_id' => null
        ]);

        $cashSale = new CashSale([
            "account_id" => $bankAccount->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $bankAccount->currency_id,
        ]);
        $cashSale->save();

        $this->assertEquals($cashSale->account->name, $bankAccount->name);
        $this->assertEquals($cashSale->account->description, $bankAccount->description);
        $this->assertEquals($cashSale->transaction_no, "CS0" . $this->period->period_count . "/0001");
    }

    /**
     * Test Posting CashSale Transaction
     *
     * @return void
     */
    public function testPostCashSaleTransaction()
    {
        $currency = factory(Currency::class)->create();
        $cashSale = new CashSale([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency->id,
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $currency->id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OPERATING_REVENUE,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);
        $lineItem->addVat(
            factory(Vat::class)->create([
                "rate" => 16
            ])
        );
        $lineItem->save();
        $cashSale->addLineItem($lineItem);

        $cashSale->post();

        $debit = Ledger::where("entry_type", Balance::DEBIT)->get()[0];
        $credit = Ledger::where("entry_type", Balance::CREDIT)->get()[0];

        $this->assertEquals($debit->post_account, $cashSale->account->id);
        $this->assertEquals($debit->folio_account, $lineItem->account_id);
        $this->assertEquals($credit->folio_account, $cashSale->account->id);
        $this->assertEquals($credit->post_account, $lineItem->account_id);
        $this->assertEquals($debit->amount, 100);
        $this->assertEquals($credit->amount, 100);

        $vat_debit = Ledger::where("entry_type", Balance::DEBIT)->get()[1];
        $vat_credit = Ledger::where("entry_type", Balance::CREDIT)->get()[1];

        $this->assertEquals($vat_debit->post_account, $cashSale->account->id);
        $this->assertEquals($vat_debit->folio_account, $lineItem->appliedVats[0]->vat->account_id);
        $this->assertEquals($vat_credit->folio_account, $cashSale->account->id);
        $this->assertEquals($vat_credit->post_account, $lineItem->appliedVats[0]->vat->account_id);
        $this->assertEquals($vat_debit->amount, 16);
        $this->assertEquals($vat_credit->amount, 16);

        $this->assertEquals($cashSale->amount, 116);
    }

    /**
     * Test Cash Sale Line Item Account.
     *
     * @return void
     */
    public function testCashSaleLineItemAccount()
    {
        $cashSale = new CashSale([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $this->expectException(LineItemAccount::class);
        $this->expectExceptionMessage('Cash Sale LineItem Account must be of type Operating Revenue');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::RECONCILIATION,
                'category_id' => null
            ])->id,
        ]);
        $lineItem->addVat(
            factory(Vat::class)->create([
                "rate" => 16
            ])
        );
        $lineItem->save();
        $cashSale->addLineItem($lineItem);

        $cashSale->post();
    }

    /**
     * Test Cash Sale Line Main Account.
     *
     * @return void
     */
    public function testCashSaleMainAccount()
    {
        $cashSale = new CashSale([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::RECONCILIATION,
                'category_id' => null
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $this->expectException(MainAccount::class);
        $this->expectExceptionMessage('Cash Sale Main Account must be of type Bank');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OPERATING_REVENUE,
                'category_id' => null
            ])->id,
        ]);
        $lineItem->addVat(
            factory(Vat::class)->create([
                "rate" => 16
            ])
        );
        $lineItem->save();
        $cashSale->addLineItem($lineItem);

        $cashSale->post();
    }

    /**
     * Test Cash Sale Find.
     *
     * @return void
     */
    public function testCashSaleFind()
    {
        $currency = factory(Currency::class)->create();
        $transaction = new CashSale([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency->id,
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $currency->id,
        ]);
        $transaction->save();

        $found = CashSale::find($transaction->id);
        $this->assertEquals($found->transaction_no, $transaction->transaction_no);
    }
}
