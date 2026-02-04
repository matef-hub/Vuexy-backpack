<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CompanyFileRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Carbon;

/**
 * Class CompanyFileCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CompanyFileCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\CompanyFile::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/company-file');
        CRUD::setEntityNameStrings('ملف شركة', 'ملفات الشركة');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('file_number')
            ->label('رقم الملف');

        CRUD::column('file_name')
            ->label('اسم الملف');

        CRUD::column('issuing_authority')
            ->label('جهة صدوره');

        CRUD::column('issue_date')
            ->label('تاريخ اصداره')
            ->type('date');

        CRUD::column('expiry_date')
            ->label('تاريخ انتهاء المستند')
            ->type('date');

        CRUD::column('is_active')
            ->label('ساري؟')
            ->type('boolean');

        CRUD::addColumn([
            'name' => 'status_text',
            'label' => 'الحالة',
            'type' => 'closure',
            'escaped' => false,
            'function' => function ($entry) {
                $isValid = (bool) ($entry->is_currently_valid ?? false);
                $label = $isValid ? 'ساري' : 'غير ساري';
                $class = $isValid ? 'bg-label-success' : 'bg-label-danger';

                return '<span class="badge '.$class.'">'.$label.'</span>';
            },
        ]);

        CRUD::orderBy('issue_date', 'desc');
        CRUD::orderBy('created_at', 'desc');

        CRUD::addFilter([
            'type' => 'dropdown',
            'name' => 'status',
            'label' => 'الحالة',
        ], [
            'valid' => 'ساري',
            'invalid' => 'غير ساري',
        ], function ($value) {
            $today = Carbon::today();

            if ($value === 'valid') {
                $this->crud->addClause('where', 'is_active', 1);
                $this->crud->addClause(function ($query) use ($today) {
                    $query->whereNull('expiry_date')
                        ->orWhereDate('expiry_date', '>=', $today);
                });
            }

            if ($value === 'invalid') {
                $this->crud->addClause(function ($query) use ($today) {
                    $query->where('is_active', 0)
                        ->orWhereDate('expiry_date', '<', $today);
                });
            }
        });

        CRUD::addFilter([
            'type' => 'dropdown',
            'name' => 'expiring_in',
            'label' => 'ينتهي خلال',
        ], [
            '7' => '7 يوم',
            '30' => '30 يوم',
            '60' => '60 يوم',
            '90' => '90 يوم',
        ], function ($value) {
            $today = Carbon::today();
            $end = Carbon::today()->addDays((int) $value);

            $this->crud->addClause('whereNotNull', 'expiry_date');
            $this->crud->addClause('whereDate', 'expiry_date', '>=', $today);
            $this->crud->addClause('whereDate', 'expiry_date', '<=', $end);
        });

        CRUD::addFilter([
            'type' => 'date_range',
            'name' => 'expiry_date',
            'label' => 'تاريخ انتهاء المستند (من - إلى)',
        ], false, function ($value) {
            $dates = json_decode($value);
            if (!empty($dates->from)) {
                $this->crud->addClause('whereDate', 'expiry_date', '>=', $dates->from);
            }
            if (!empty($dates->to)) {
                $this->crud->addClause('whereDate', 'expiry_date', '<=', $dates->to);
            }
        });

        /**
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(CompanyFileRequest::class);

        CRUD::addField([
            'name' => 'file_number',
            'label' => 'رقم الملف',
            'type' => 'text',
            'attributes' => [
                'required' => 'required',
            ],
        ]);

        CRUD::addField([
            'name' => 'file_name',
            'label' => 'اسم الملف',
            'type' => 'text',
            'attributes' => [
                'required' => 'required',
            ],
        ]);

        CRUD::addField([
            'name' => 'issuing_authority',
            'label' => 'جهة صدوره',
            'type' => 'text',
        ]);

        CRUD::addField([
            'name' => 'issue_date',
            'label' => 'تاريخ اصداره',
            'type' => 'date',
        ]);

        CRUD::addField([
            'name' => 'expiry_date',
            'label' => 'تاريخ انتهاء المستند',
            'type' => 'date',
        ]);

        CRUD::addField([
            'name' => 'is_active',
            'label' => 'ساري؟',
            'type' => 'switch',
            'default' => true,
        ]);

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
