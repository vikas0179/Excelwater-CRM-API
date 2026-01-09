<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminAPIController;
use App\Http\Controllers\UserAPIController;
use App\Http\Controllers\HomeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Admin
Route::group(['prefix' => 'admin', 'as' => 'admin.'], function () {

	Route::post('leads/save', [AdminAPIController::class, 'lead_save']);

	Route::post('get_invoice_detail_old', [HomeController::class, 'GetInvoiceDetailOld']);
	Route::post('get_invoice_detail', [HomeController::class, 'GetInvoiceDetail']);
	Route::post('scan_exit_qr_code', [HomeController::class, 'ScanIsExitQrCode']);

	Route::get('draft_invoice', [HomeController::class, 'DraftInvoice']);
	Route::post('save_invoice', [HomeController::class, 'SaveInvoice']);
	Route::get('send_mail', [HomeController::class, 'MailSend']);
	Route::get('check_invoice_no', [HomeController::class, 'CheckInvoiceNo']);

	Route::post('login', [AdminAPIController::class, 'login']);
	Route::post('/', [AdminAPIController::class, 'login']);

	Route::post('forgot-password', [HomeController::class, 'forgot_password']);
	Route::post('verify-token', [HomeController::class, 'verify_token']);
	Route::post('reset-password', [HomeController::class, 'reset_password']);

	Route::get('get-data', [AdminAPIController::class, 'get_data']);
	Route::get('get-static-data', [AdminAPIController::class, 'get_static_data']);

	Route::group(['middleware' => 'jwt.verify'], function () {

		Route::get('get-lead-statistics', [AdminAPIController::class, 'leads_statistics']);
		Route::post('update-ads-spends', [AdminAPIController::class, 'update_ads_spends']);

		Route::get('get-profile', [AdminAPIController::class, 'get_profile']);
		Route::post('update-profile', [AdminAPIController::class, 'update_profile']);
		Route::post('change-password', [AdminAPIController::class, 'change_password']);

		Route::post('add-lead', [AdminAPIController::class, 'add_lead']);
		Route::post('update-lead/{id}', [AdminAPIController::class, 'update_lead']);
		Route::get('lead-detail/{id}', [AdminAPIController::class, 'lead_detail']);
		Route::get('lead-delete/{id}', [AdminAPIController::class, 'delete_lead']);
		Route::get('/leads-message/{id}', [AdminAPIController::class, 'lead_message']);

		Route::get('logout', [AdminAPIController::class, 'logout']);

		Route::get('get-dashboard', [AdminAPIController::class, 'get_admin_dashboard']);

		Route::group(['prefix' => 'users', 'as' => 'users.'], function () {
			Route::post('add', [AdminAPIController::class, 'add_sub_user']);
			Route::get('/', [AdminAPIController::class, 'get_sub_users']);
			Route::get('/list', [AdminAPIController::class, 'get_sub_users']);
			Route::get('/search-sales-person', [AdminAPIController::class, 'search_sales_person']);
			Route::get('/{id}', [AdminAPIController::class, 'get_sub_user']);
			Route::post('update', [AdminAPIController::class, 'update_sub_user']);
			Route::get('status/{id}', [AdminAPIController::class, 'sub_user_status_update']);
		});

		Route::get('get-leads', [AdminAPIController::class, 'get_admin_leads']);
		Route::get('get-duplicate-leads', [AdminAPIController::class, 'get_admin_duplicate_leads']);
		Route::post('assign-lead', [AdminAPIController::class, 'admin_assign_lead']);
		Route::get('get-assigned-leads/{user_id}', [AdminAPIController::class, 'get_assigned_lead']);
		Route::post('trasnsfer-leads', [AdminAPIController::class, 'admin_transfer_leads']);

		Route::get('lead-details/{id}', [AdminAPIController::class, 'get_lead_details']);
		Route::get('lead-history/{id}', [AdminAPIController::class, 'get_lead_history']);
		Route::get('report/revenue', [AdminAPIController::class, 'get_revenue_report']);

		Route::get('/get-all-products', [AdminAPIController::class, 'get_all_products']);
		Route::get('/get-all-sales-channels', [AdminAPIController::class, 'get_all_sales_channel']);

		Route::get('/get-pending-installations', [AdminAPIController::class, 'get_pending_installations']);
		Route::get('/get-pending-water-test', [AdminAPIController::class, 'get_pending_water_test']);
		Route::get('/get-upcoming-followups', [AdminAPIController::class, 'get_upcoming_followups']);
		Route::get('lead-last-comment/{id}', [AdminAPIController::class, 'get_lead_last_comment']);
		Route::get('duplicate-leads-combined/{id}', [AdminAPIController::class, 'duplicate_leads_combined']);
		Route::get('export-leads', [AdminAPIController::class, 'export_leads']);

		Route::group(['prefix' => 'sales', 'as' => 'sales.'], function () {

			Route::get('get-sales-dashboard', [AdminAPIController::class, 'get_sales_dashboard']);

			Route::get('/get-leads', [AdminAPIController::class, 'get_sales_user_assigned_leads']);

			Route::post('/send-estimates', [AdminAPIController::class, 'send_estimate']);
			Route::post('/installation/completed/{id}', [AdminAPIController::class, 'installation_compete']);
			Route::post('/water-test/completed/{id}', [AdminAPIController::class, 'water_test_compete']);


			//Route::post('update', [AdminAPIController::class,'update_sub_user']);
			Route::post('lead/status-update', [AdminAPIController::class, 'get_sales_user_status_update']);
			Route::get('lead/details/{id}', [AdminAPIController::class, 'get_sales_user_lead_details']);
			Route::get('lead/history/{id}', [AdminAPIController::class, 'get_sales_user_lead_history']);
			Route::post('lead/update', [AdminAPIController::class, 'update_sales_user_lead']);

			Route::post('channel/add', [AdminAPIController::class, 'add_sales_channel']);
			Route::post('channel/update/{id}', [AdminAPIController::class, 'update_sales_channel']);
			Route::get('channel/details/{id}', [AdminAPIController::class, 'sales_channel_detail']);
			Route::get('channel/list', [AdminAPIController::class, 'sales_channel_list']);
			Route::get('channel/delete/{id}', [AdminAPIController::class, 'delete_sales_channel']);
		});

		// Suppliers
		Route::get('suppliers', [AdminAPIController::class, 'get_suppliers']);
		Route::post('add_supplier', [AdminAPIController::class, 'add_supplier']);
		Route::post('edit_supplier', [AdminAPIController::class, 'edit_supplier']);
		Route::post('delete_supplier', [AdminAPIController::class, 'delete_supplier']);
		Route::get('get_supplier/{id}', [AdminAPIController::class, 'get_supplier']);
		Route::post('get_all_supplier', [AdminAPIController::class, 'get_all_supplier']);
		Route::post('get_supplier_wise_sparepart', [AdminAPIController::class, 'getsupplierWiseSparePart']);

		// Product Master
		Route::get('product_master', [AdminAPIController::class, 'product_master']);
		Route::post('add_product_master', [AdminAPIController::class, 'add_product_master']);
		Route::post('edit_product_master', [AdminAPIController::class, 'edit_product_master']);
		Route::post('delete_product_master', [AdminAPIController::class, 'delete_product_master']);
		Route::get('get_product_master/{id}', [AdminAPIController::class, 'get_product_master']);
		Route::post('get_all_ptoduct', [AdminAPIController::class, 'get_all_product']);

		// Spare Parts
		Route::get('spare_parts', [AdminAPIController::class, 'spare_parts']);
		Route::post('add_spare_part', [AdminAPIController::class, 'add_spare_part']);
		Route::post('edit_spare_part', [AdminAPIController::class, 'edit_spare_part']);
		Route::post('delete_spare_part', [AdminAPIController::class, 'delete_spare_part']);
		Route::get('get_spare_part/{id}', [AdminAPIController::class, 'get_spare_part']);
		Route::post('get_all_spare_part', [AdminAPIController::class, 'get_all_spare_part']);

		// Order
		Route::get('orders', [AdminAPIController::class, 'orders']);
		Route::post('add_order', [AdminAPIController::class, 'add_order']);
		Route::get('get_order/{id}', [AdminAPIController::class, 'get_order']);
		Route::post('delete_order', [AdminAPIController::class, 'delete_order']);
		Route::post('edit_order', [AdminAPIController::class, 'edit_order']);
		Route::post('edit_order_details', [AdminAPIController::class, 'edit_order_details']);

		// Invoice
		Route::get('invoices', [AdminAPIController::class, 'invoices']);
		Route::get('void_invoices', [AdminAPIController::class, 'VoidInvoices']);
		Route::post('add_invoice', [AdminAPIController::class, 'add_invoice']);
		Route::get('get_invoice/{id}', [AdminAPIController::class, 'get_invoice']);
		Route::post('delete_invoice', [AdminAPIController::class, 'delete_invoice']);
		Route::post('edit_invoice', [AdminAPIController::class, 'edit_invoice']);
		// Route::post('check_use_parts', [AdminAPIController::class, 'check_use_parts']);
		Route::post('change_void_status', [AdminAPIController::class, 'ChangeVoidStatus']);
		Route::post('invoice_send_mail', [AdminAPIController::class, 'invoiceMailSend']);
		Route::post('settle_payment', [AdminAPIController::class, 'SettlePayment']);
		Route::get('transaction_summary/{id}', [AdminAPIController::class, 'TransactionSummary']);
		Route::post('payment_settlement', [AdminAPIController::class, 'InvoicePaymentSettlement']);
		Route::post('getpayment_settlement', [AdminAPIController::class, 'GetInvoicePaymentSettlement']);
		Route::get('get_activity_log', [AdminAPIController::class, 'GetActivityLog']);

		// Users
		Route::get('get_users', [AdminAPIController::class, 'GetUsers']);
		Route::post('add_user', [AdminAPIController::class, 'AddUser']);
		Route::post('edit_user', [AdminAPIController::class, 'EditUser']);
		Route::post('delete_user', [AdminAPIController::class, 'DeleteUser']);
		Route::get('dashboard_all_count', [AdminAPIController::class, 'DashboardAllCount']);

		// Employee
		Route::get('get_employee', [AdminAPIController::class, 'GetEmployee']);
		Route::post('add_employee', [AdminAPIController::class, 'AddEmployee']);
		Route::post('delete_employee', [AdminAPIController::class, 'DeleteEmployee']);
		Route::post('edit_employee', [AdminAPIController::class, 'EditEmployee']);
		Route::post('change_employee_status', [AdminAPIController::class, 'ChangeEmployeeStatus']);

		// Product Store
		Route::post('get_product_store', [AdminAPIController::class, 'GetProductStore']);
		Route::post('add_product_store', [AdminAPIController::class, 'AddProductStore']);

		Route::get('get_materia_report', [AdminAPIController::class, 'GetMaterialReport']);
		Route::post('get_product_report', [AdminAPIController::class, 'GetProductReportDashboard']);
		Route::post('get_material_report', [AdminAPIController::class, 'GetMaterialReportDashboard']);

		// Manage Category
		Route::get('manage_category', [AdminAPIController::class, 'manage_category']);
		Route::post('add_category', [AdminAPIController::class, 'add_category']);
		Route::post('update_category', [AdminAPIController::class, 'update_category']);
		Route::post('delete_category', [AdminAPIController::class, 'delete_category']);
		Route::get('get_specific_category/{id}', [AdminAPIController::class, 'get_specific_category']);

		// Manage Sub Category
		Route::get('manage_sub_category', [AdminAPIController::class, 'manage_sub_category']);
		Route::post('add_sub_category', [AdminAPIController::class, 'add_sub_category']);
		Route::post('update_sub_category', [AdminAPIController::class, 'update_sub_category']);
		Route::post('delete_sub_category', [AdminAPIController::class, 'delete_sub_category']);
		Route::get('get_specific_sub_category/{id}', [AdminAPIController::class, 'get_specific_sub_category']);

		// Manage Product Attribute
		Route::get('manage_product_attribute', [AdminAPIController::class, 'manage_product_attribute']);
		Route::post('add_product_attribute', [AdminAPIController::class, 'add_product_attribute']);
		Route::post('update_product_attribute', [AdminAPIController::class, 'update_product_attribute']);
		Route::post('delete_product_attribute', [AdminAPIController::class, 'delete_product_attribute']);
		Route::get('get_specific_product_attribute/{id}', [AdminAPIController::class, 'get_specific_product_attribute']);
		Route::post('get_product_attribute', [AdminAPIController::class, 'get_product_attribute']);

		// Manage Product Attribute Items
		Route::get('manage_product_attribute_items', [AdminAPIController::class, 'manage_product_attribute_items']);
		Route::post('add_product_attribute_items', [AdminAPIController::class, 'add_product_attribute_items']);
		Route::post('update_product_attribute_items', [AdminAPIController::class, 'update_product_attribute_items']);
		Route::post('delete_product_attribute_items', [AdminAPIController::class, 'delete_product_attribute_items']);
		
		// Manage Product Review
		Route::get('manage_product_review', [AdminAPIController::class, 'manage_product_review']);
		Route::post('change_product_review_status', [AdminAPIController::class, 'change_product_review_status']);
		Route::post('delete_product_review', [AdminAPIController::class, 'delete_product_review']);

		// Manage Banner
		Route::get('manage_banner', [AdminAPIController::class, 'manage_banner']);
		Route::post('add_banner', [AdminAPIController::class, 'add_banner']);
		Route::post('delete_banner', [AdminAPIController::class, 'delete_banner']);

		// Manage Tax
		Route::get('manage_tax_setting', [AdminAPIController::class, 'manage_tax_setting']);
		Route::post('change_tax_setting', [AdminAPIController::class, 'change_tax_setting']);

		// Brand
		Route::get('get_brands', [AdminAPIController::class, 'get_brands']);
		Route::post('add_brand', [AdminAPIController::class, 'add_brand']);
		Route::post('update_brand', [AdminAPIController::class, 'update_brand']);
		Route::post('delete_brand', [AdminAPIController::class, 'delete_brand']);
		Route::get('get_brand/{id}', [AdminAPIController::class, 'get_brand']);
	});
});





// Users
Route::group(['prefix' => 'user', 'as' => 'user.'], function () {

	Route::post('login',[UserAPIController::class,'login']);                                               	// Done
	Route::post('registration',[UserAPIController::class,'registration']);                                 	// Done
	Route::post('forgot-password', [UserAPIController::class,'forgot_password']);                          	// Done
	Route::post('verify-token', [UserAPIController::class,'verify_token']);                                	// Done
	Route::post('reset-password', [UserAPIController::class,'reset_password']);                            	// Done
	Route::post('place-order',[UserAPIController::class,'place_order']);									// Done
	Route::get('product-list',[UserAPIController::class,'product_list']);      								// Done
	Route::get('product-details',[UserAPIController::class,'product_details']);								// Done
	Route::post('get-shopping-cart-products',[UserAPIController::class,'get_shopping_cart_products']);   	// Done
	Route::get('get-data',[UserAPIController::class,'get_data']);											// Done
	Route::get('get-filter-data',[UserAPIController::class,'get_filter_data']);								// Done
	Route::post('contact-us',[UserAPIController::class,'contact_us']);										// Done
	Route::post('partner-with-us',[UserAPIController::class,'partner_with_us']);							// Done
	Route::get('home',[UserAPIController::class,'home_page']);												// Done
	Route::get('get-gift-card-amounts',[UserAPIController::class,'get_gift_card_amounts']);					// Done
	Route::post('gift-card-place-order',[UserAPIController::class,'gift_card_place_order']);				// Done
	Route::post('apply-gift-card',[UserAPIController::class,'apply_gift_card']);							// Done
	Route::post('apply-gift-card-otp',[UserAPIController::class,'apply_gift_card_otp']);					// Done
	Route::post('payment-success',[UserAPIController::class,'payment_success']);							// Done
	Route::post('payment-fail',[UserAPIController::class,'payment_fail']);									// Done
	// Jwt Verify
	Route::group(['middleware' => 'jwt.verify'], function(){
		Route::get('logout', [UserAPIController::class,'logout']);											// Done
		Route::get('get-profile', [UserAPIController::class,'get_profile']);                				// Done
		Route::post('update-profile', [UserAPIController::class,'update_profile']);							// Done
		Route::post('change-password', [UserAPIController::class, 'change_password']);      				// Done
		Route::post('add-address', [UserAPIController::class, 'add_address']); 								// Done
		Route::post('update-address', [UserAPIController::class, 'update_address']);        				// Done
		Route::get('address-list', [UserAPIController::class, 'address_list']);								// Done
		Route::get('get-address', [UserAPIController::class, 'get_address']);								// Done
		Route::get('delete-address', [UserAPIController::class, 'delete_address']);         				// Done
		Route::get('order-list',[UserAPIController::class,'order_list']);                   				// Done
		Route::get('order-details',[UserAPIController::class,'order_details']);								// Done
		Route::post('product-review', [UserAPIController::class, 'product_review']);						// Done
		Route::get('print-invoice/{id}',[UserAPIController::class,'print_invoice']);						// Done	
		Route::get('gift-card-order-list',[UserAPIController::class,'gift_card_order_list']);				// Done
		Route::get('gift-card-order-details/{id}',[UserAPIController::class,'gift_card_order_details']);	// Done
	}); 
});
