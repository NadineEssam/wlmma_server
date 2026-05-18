<?php

use App\Http\Controllers\Admin\AdminActivityController;
use App\Http\Controllers\Admin\AdminChatController;
use App\Http\Controllers\Admin\AdminCommercialToolController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CashbackController;
use App\Http\Controllers\Admin\FaqsController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Auth\AppleAuthController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\LandingPage\LandingPageContentController;
use App\Http\Controllers\Permission\PermissionController;
use App\Http\Controllers\Role\RoleController;
use App\Http\Controllers\ServiceProvider\ActivityController;
use App\Http\Controllers\ServiceProvider\CommercialToolController;
use App\Http\Controllers\ServiceProvider\OfferController;
use App\Http\Controllers\ServiceProvider\ProviderCartController;
use App\Http\Controllers\ServiceProvider\ProviderOrderController;
use App\Http\Controllers\ServiceProvider\ServiceProviderChatController;
use App\Http\Controllers\ServiceProvider\ServiceProviderController;
use App\Http\Controllers\User\BillController;
use App\Http\Controllers\User\BookingController;
use App\Http\Controllers\User\CartController;
use App\Http\Controllers\User\OrderController;
use App\Http\Controllers\User\RatingController;
use App\Http\Controllers\User\RatingToolController;
use App\Http\Controllers\User\TestController;
use App\Http\Controllers\User\UserActivityController;
use App\Http\Controllers\User\UserCommercialToolsController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\ApplePayController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\FCMController;
use App\Http\Controllers\FirebaseAuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SmsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * |--------------------------------------------------------------------------
 * | API Routes
 * |--------------------------------------------------------------------------
 * |
 * | Here is where you can register API routes for your application. These
 * | routes are loaded by the RouteServiceProvider and all of them will
 * | be assigned to the "api" middleware group. Make something great!
 * |
 */

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('/send-sms', [SmsController::class, 'sendTestSms']);

Route::get('/fcm-token', [FCMController::class, 'index']);
Route::get('/payment/form/{CheckoutID}', [PaymentController::class, 'form']);

// Public Invoice PDF Download (no auth required - for email links)
Route::get('/invoices/{id}/pdf', [InvoiceController::class, 'downloadPdf']);
Route::get('/invoices/{id}/view', [InvoiceController::class, 'viewPdf']);

Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);
Route::post('/auth/google-signin', [GoogleController::class, 'googleSignIn']);

Route::post('/auth/apple-signin', [AppleAuthController::class, 'appleSignIn']);

Route::group(['prefix' => 'user'], function () {
    // Notification OLD
    Route::post('/notification/send-to-one', [NotificationController::class, 'sendToOne']);
    Route::post('/notification/send-to-many', [NotificationController::class, 'sendToMany']);
    Route::get('/testNotification', [NotificationController::class, 'testNotification']);
    // Notification OLD

    Route::post('/generate-otp', [UserController::class, 'generateOTP']);
    Route::post('/verify-otp', [UserController::class, 'verifyOTP']);
    Route::post('/upgradeToProvider', [UserController::class, 'upgradeToProvider']);
    Route::post('/switchUserRole', [UserController::class, 'switchUserRole']);

    Route::group(['middleware' => 'auth:users'], function () {
        Route::post('/add_phoneNumber', [UserController::class, 'add_phoneNumber']);
        Route::delete('/deleteAccount', [UserController::class, 'deleteAccount']);
        Route::get('/deactivateAccount', [UserController::class, 'deactivateAccount']);

        // Chat
        Route::prefix('chat')->group(function () {
            Route::post('/last_chat/{roomId}', [ChatController::class, 'getLatestMessage']);  // Start a new chat room
            Route::post('/start', [ChatController::class, 'startChat']);  // Start a new chat room
            Route::post('/send', [ChatController::class, 'sendMessage']);  // Send a message to the room
            Route::get('/messages/{roomId}', [ChatController::class, 'getMessages']);  // Get messages from the room
            Route::post('/close/{roomId}', [ChatController::class, 'closeChat']);
            Route::get('/getAllRooms', [ChatController::class, 'getAllRooms']);  // Get All rooms
            Route::get('/getUserRooms', [ChatController::class, 'getUserRooms']);  // Get All getUserRooms
        });
        // Chat

        // Save Or Update FCM_token
        Route::post('/saveFcmToken', [UserController::class, 'saveFcmToken']);
        // Save Or Update FCM_token

        // Notification
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'getList']);
            Route::get('/unseen-count', [NotificationController::class, 'unseenCount']);
            Route::post('/seen', [NotificationController::class, 'seen']);
            Route::get('/seen-all', [NotificationController::class, 'seenAll']);
            // Route::post('/push', [NotificationController::class, 'push']);

            Route::prefix('test')->group(function () {
                Route::post('/save-only', [NotificationController::class, 'saveOnly']);
                Route::post('/push-only', [NotificationController::class, 'pushOnly']);
                Route::post('/save-push', [NotificationController::class, 'saveAndPush']);
                Route::post('/push_welcome/{user_id}', [NotificationController::class, 'push_welcome']);
                Route::post('/pushWithoutBookId2', [NotificationController::class, 'pushWithoutBookId2']);
            });
        });
        // Notification

        // Register
        Route::prefix('register')->group(function () {
            Route::post('/company', [UserController::class, 'registerCompany']);
            Route::post('/customer', [UserController::class, 'registerUser']);
            Route::post('/individual', [UserController::class, 'registerIndividual']);
        });
        // Register
    });
});

Route::group(['prefix' => 'admin'], function () {
    Route::group(['prefix' => 'auth'], function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    });

    Route::group(['prefix' => 'users'], function () {
        Route::post('/approve', [AdminController::class, 'acceptProvider']);
        Route::get('/providerRequests', [AdminController::class, 'providerRequests']);
        Route::get('/deactivateAccountRequests', [AdminController::class, 'deactivateAccountRequests']);
        Route::get('/providerRequests_details/{user_id}', [AdminController::class, 'providerRequests_details']);
        Route::get('/details/{user_id}', [AdminController::class, 'user_details']);
    });

    Route::group(['middleware' => 'auth:admins'], function () {
        Route::get('/me', [AdminController::class, 'me']);
        Route::get('/logout', [AuthController::class, 'logout']);
        Route::get('/list-admin-permissions', [AdminController::class, 'listAdminPermissions']);
        Route::get('/home', [AdminController::class, 'dashboard']);

        // Notification
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'getList']);
            Route::get('/unseen-count', [NotificationController::class, 'unseenCount']);
            Route::post('/seen', [NotificationController::class, 'seen']);
            Route::get('/seen-all', [NotificationController::class, 'seenAll']);
            Route::post('/push', [NotificationController::class, 'push']);

            Route::prefix('test')->group(function () {
                Route::post('/save-only', [NotificationController::class, 'saveOnly']);
                Route::post('/push-only', [NotificationController::class, 'pushOnly']);
                Route::post('/save-push', [NotificationController::class, 'saveAndPush']);
            });
        });
        // Notification

        // Chat
        Route::prefix('chat')->group(function () {
            Route::post('/start', [ChatController::class, 'startChat']);  // Start a new chat room
            Route::post('/send', [ChatController::class, 'sendMessage_admin']);  // Send a message to the room
            Route::post('/last_chat/{roomId}', [ChatController::class, 'getLatestMessage']);  // Start a new chat room
            Route::get('/messages/{roomId}', [ChatController::class, 'getMessages']);  // Get messages from the room
            Route::post('/close/{roomId}', [ChatController::class, 'closeChat']);
            Route::get('/getAllRooms', [ChatController::class, 'getAllRooms']);  // Get All rooms
            Route::get('/getUserRooms', [ChatController::class, 'getUserRooms']);  // Get All getUserRooms
        });
        // Chat

        Route::post('/saveFcmToken', [AdminController::class, 'saveFcmToken']);
        Route::group(['prefix' => 'orders'], function () {
            Route::get('/', [AdminActivityController::class, 'orders']);
            Route::get('/order_dtails/{order_id}', [AdminActivityController::class, 'order_details']);
        });
    });
});

// Public routes
Route::prefix('v1')->group(function () {
    // Authentication routes
    Route::post('user/generate-otp', [UserController::class, 'generateOtp']);
    Route::post('user/verify-otp', [UserController::class, 'verifyOtp']);

    // Public activity routes

    Route::get('activities', [ActivityController::class, 'index']);
    Route::get('activities/{activity}', [ActivityController::class, 'show']);
    Route::post('/activities/store', [ActivityController::class, 'store']);
});

Route::group(['prefix' => 'manager', 'middleware' => 'auth:admins'], function () {
    Route::group(['prefix' => 'admin'], function () {
        Route::post('/assign-role', [AdminController::class, 'assignRoleToAdmin'])->middleware('role:roleManaging');

        Route::post('/revoke-role', [AdminController::class, 'revokeRoleFromAdmin'])->middleware('role:roleManaging');
    });
});

Route::group(['prefix' => 'role'], function () {
    Route::get('/list', [RoleController::class, 'list'])->middleware('role:roleManaging');
    Route::post('/create-permission-role', [RoleController::class, 'createPermissionRole'])->middleware('role:roleManaging');
});

Route::group(['prefix' => 'permission'], function () {
    Route::get('/list', [PermissionController::class, 'list'])->middleware('role:roleManaging');
});

// Protected routes

Route::get('/tour_guide', [ServiceProviderController::class, 'tour_guide']);
Route::group(['prefix' => 'service-provider/offers'], function () {
    Route::get('/', [OfferController::class, 'index']);
    Route::get('/{offer}', [OfferController::class, 'show']);
    Route::post('/store', [OfferController::class, 'store']);
    Route::put('/{offer}', [OfferController::class, 'update']);
    Route::delete('/{offer}', [OfferController::class, 'destroy']);
});
Route::group(['middleware' => 'auth:users'], function () {
    // User registration routes
    // Route::post('user/register/customer', [UserController::class, 'registerCustomer']);
    // Route::post('user/register/company', [UserController::class, 'registerCompany']);
    // Route::post('user/register/individual', [UserController::class, 'registerIndividual']);

    // Protected Service Provider routes
    Route::group(['middleware' => 'isServiceProvider', 'prefix' => 'service-provider'], function () {
        Route::get('/RequestedDataForCreateCheckout', [UserController::class, 'RequestedDataForCreateCheckout']);

        // Service Provider profile routes
        Route::get('/profile', [ServiceProviderController::class, 'profile']);
        Route::put('/update_profile', [ServiceProviderController::class, 'updateProfile']);

        Route::post('/OTPforProfile', [UserController::class, 'generateOTPforUpdateProfile']);
        Route::post('/update_profile', [UserController::class, 'updateProfile']);

        Route::get('/dashboard', [ServiceProviderController::class, 'dashboard']);

        Route::group(['prefix' => 'activities'], function () {
            Route::get('', [ActivityController::class, 'index']);
            Route::get('/booking_requests', [ActivityController::class, 'show_booking_request']);
            Route::put('/booking_requests/action', [ActivityController::class, 'booking_request_action']);
            Route::post('/qrcode_search', [ActivityController::class, 'qrcode_search']);
            // Route::get('/{serviceProviderId}/{perPage}', [ActivityController::class, 'index']);
            Route::get('/{activity}', [ActivityController::class, 'show']);
            Route::get('/showBookingDetails/{id}', [ActivityController::class, 'showBookingDetails']);
            Route::post('/store', [ActivityController::class, 'store']);
            Route::put('/{activity}', [ActivityController::class, 'update']);
            Route::delete('/{activity}', [ActivityController::class, 'destroy']);
        });

        // Notification
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'getList']);
            Route::get('/unseen-count', [NotificationController::class, 'unseenCount']);
            Route::post('/seen', [NotificationController::class, 'seen']);
            Route::get('/seen-all', [NotificationController::class, 'seenAll']);
            // Route::post('/push', [NotificationController::class, 'push']);

            Route::prefix('test')->group(function () {
                Route::post('/save-only', [NotificationController::class, 'saveOnly']);
                Route::post('/push-only', [NotificationController::class, 'pushOnly']);
                Route::post('/save-push', [NotificationController::class, 'saveAndPush']);
            });
        });
        // Notification

        // Chat
        Route::prefix('chat')->group(function () {
            Route::post('/last_chat/{roomId}', [ChatController::class, 'getLatestMessage']);  // Start a new chat room
            Route::post('/start', [ChatController::class, 'startChat']);  // Start a new chat room
            Route::post('/send', [ChatController::class, 'sendMessage']);  // Send a message to the room
            Route::get('/messages/{roomId}', [ChatController::class, 'getMessages']);  // Get messages from the room
            Route::post('/close/{roomId}', [ChatController::class, 'closeChat']);
            Route::get('/getAllRooms', [ChatController::class, 'getAllRooms']);  // Get All rooms
            Route::get('/getUserRooms', [ChatController::class, 'getUserRooms']);  // Get All getUserRooms
        });
        // Chat

        // Save Or Update FCM_token
        Route::post('/saveFcmToken', [UserController::class, 'saveFcmToken']);
        // Save Or Update FCM_token

        // Route::group(['prefix' => 'commercial-tools'], function () {
        //     Route::post('/', [CommercialToolController::class, 'store']);
        //     Route::get('/', [CommercialToolController::class, 'index']);
        //     Route::get('/{tool}', [CommercialToolController::class, 'show']);
        //     Route::get('/types', [CommercialToolController::class, 'getToolTypes']);
        // });

        Route::group(['prefix' => 'commercial-tools'], function () {
            Route::get('/', [CommercialToolController::class, 'index']);
            Route::post('/store', [CommercialToolController::class, 'store']);
            Route::get('/allSuppliersTools', [CommercialToolController::class, 'getAllSuppliersTools']);
            Route::get('/allSuppliersToolsForProvider', [CommercialToolController::class, 'getAllSuppliersToolsForProvider']);
            Route::post('/update/{tool}', [CommercialToolController::class, 'update']);
            Route::get('/{tool}', [CommercialToolController::class, 'show']);
            Route::delete('/{tool}', [CommercialToolController::class, 'destroy']);
            Route::get('/types', [CommercialToolController::class, 'getToolTypes']);
            Route::post('/suppliesRentforProvider', [CommercialToolController::class, 'suppliesRentforProvider']);
            Route::post('/rentFromSuppliers', [CommercialToolController::class, 'forRentFromSuppliers']);
            Route::post('/forRent', [CommercialToolController::class, 'forRent']);
            Route::post('/forSale', [CommercialToolController::class, 'forSale']);
            Route::group(['prefix' => 'cart'], function () {
                // Add item to cart
                Route::post('/add', [ProviderCartController::class, 'addToCart']);
                // Update cart item quantity
                Route::put('/update', [ProviderCartController::class, 'updateCartItem']);
                // Remove an item from the cart
                Route::post('/remove', [ProviderCartController::class, 'removeFromCart']);
                // Get cart items
                Route::post('/', [ProviderCartController::class, 'showCart']);
                // Clear cart
                Route::delete('/clear', [ProviderCartController::class, 'clearCart']);
                // Checkout
                Route::post('/checkout', [ProviderCartController::class, 'checkout']);
            });
        });
        // Orders
        Route::group(['prefix' => 'orders'], function () {
            Route::get('/', [ProviderOrderController::class, 'indexProvider']);
            Route::get('/show/{id}', [ActivityController::class, 'order_details']);
            Route::post('/add', [ProviderOrderController::class, 'addToOrderFromCart']);
        });
    });
    // Orders

    // Add other protected routes here...
});

Route::group(['middleware' => 'auth:admins', 'prefix' => 'landing-page-content'], function () {
    Route::post('/saveContent', [LandingPageContentController::class, 'saveContent']);
    Route::put('/updateContent', [LandingPageContentController::class, 'updateContent']);

    Route::delete('/destroy', [LandingPageContentController::class, 'destroy']);
});

Route::group(['prefix' => 'landing-page-content'], function () {
    // Route::get('index', [LandingPageContentController::class, 'getContentByPageId']);
    Route::get('/index/{page_id}', [LandingPageContentController::class, 'getContentByPageId']);
    Route::get('/', [LandingPageContentController::class, 'getAllPagesWithContent']);
});

Route::group(['middleware' => 'auth:admins', 'prefix' => 'dashboard'], function () {
    // ZATCA Invoice Management
    Route::group(['prefix' => 'invoices'], function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::get('/statistics', [InvoiceController::class, 'statistics']);
        Route::get('/zatca-status', [InvoiceController::class, 'zatcaStatus']);
        Route::get('/{id}', [InvoiceController::class, 'show']);
        Route::get('/{id}/xml', [InvoiceController::class, 'downloadXml']);
        Route::get('/{id}/qr-code', [InvoiceController::class, 'getQrCode']);
        Route::post('/{id}/retry', [InvoiceController::class, 'retry']);
    });

    Route::group(['prefix' => 'sliders'], function () {
        Route::get('/', [OfferController::class, 'index']);
        Route::get('/{offer}', [OfferController::class, 'show']);
        Route::post('/store', [OfferController::class, 'store']);
        Route::post('/{offer}', [OfferController::class, 'update']);
        Route::delete('/{offer}', [OfferController::class, 'destroy']);
    });

    // Route::get('', [AdminActivityController::class, 'index']);

    Route::group(['prefix' => 'adventures'], function () {
        Route::get('/', [AdminActivityController::class, 'activityTypes']);
        Route::post('/store', [AdminActivityController::class, 'storeActivityTypes']);
        Route::get('/{activityType}', [AdminActivityController::class, 'showActivityTypes']);
        Route::post('/{activityType}', [AdminActivityController::class, 'updateActivityTypes']);
        Route::delete('/{activityType}', [AdminActivityController::class, 'destroyActivityTypes']);
    });

    Route::group(['prefix' => 'settings'], function () {
        Route::post('/store', [SettingsController::class, 'store']);
        Route::put('/update/{id}', [SettingsController::class, 'update']);
        Route::get('/{id}', [SettingsController::class, 'find']);
        Route::delete('/delete/{id}', [SettingsController::class, 'delete']);
    });

    Route::group(['prefix' => 'cashback'], function () {
        Route::get('/', [CashbackController::class, 'index']);
        Route::post('/store', [CashbackController::class, 'store']);
    });

    Route::group(['prefix' => 'faqs'], function () {
        Route::get('/', [FaqsController::class, 'index']);
        Route::post('/store', [FaqsController::class, 'store']);
        Route::post('/update/{id}', [FaqsController::class, 'update']);
        Route::get('/{id}', [FaqsController::class, 'find']);
        Route::delete('/delete/{id}', [FaqsController::class, 'delete']);
    });

    Route::group(['prefix' => 'admin_activities'], function () {
        Route::get('', [AdminActivityController::class, 'admin_index']);
        Route::get('/{activity}', [AdminActivityController::class, 'show']);
        Route::post('/store', [AdminActivityController::class, 'admin_store']);
        Route::put('/{activity}', [AdminActivityController::class, 'admin_update']);
        Route::delete('/{activity}', [AdminActivityController::class, 'admin_destroy']);
        Route::get('/showBookingDetails/{id}', [AdminActivityController::class, 'showBookingDetails']);
        Route::get('/alltripBookings/{id}', [AdminActivityController::class, 'alltripBookings']);
        Route::post('/featured_trips', [AdminActivityController::class, 'featured_trips']);
    });
    Route::group(['prefix' => 'commercial-tools'], function () {
        Route::get('/', [AdminCommercialToolController::class, 'index']);
        Route::get('toolorders/{tool}', [AdminCommercialToolController::class, 'toolorders']);
        Route::post('/forRent', [AdminCommercialToolController::class, 'forRent']);
        Route::post('/forSale', [AdminCommercialToolController::class, 'forSale']);
        Route::post('/store', [AdminCommercialToolController::class, 'store']);
        Route::post('/update/{tool}', [AdminCommercialToolController::class, 'update']);
        Route::get('/{tool}', [AdminCommercialToolController::class, 'show']);
        Route::delete('/{tool}', [AdminCommercialToolController::class, 'destroy']);
        Route::get('/types', [AdminCommercialToolController::class, 'getToolTypes']);
    });
    Route::group(['prefix' => 'users'], function () {
        Route::get('/', [AdminUserController::class, 'index']);
        Route::get('/type_filter', [AdminUserController::class, 'type_filter']);
        // Route::post('/store', [AdminUserController::class, 'store']);
        // Route::post('/update/{tool}', [AdminUserController::class, 'update']);
        // Route::get('/{tool}', [AdminUserController::class, 'show']);
        // Route::delete('/{tool}', [AdminUserController::class, 'destroy']);
        // Route::get('/types', [AdminUserController::class, 'getToolTypes']);
    });
});

// Customer ( Individual User )

Route::group(['middleware' => 'auth:users'], function () {
    // HYPERPAY
    Route::post('/create-checkout', [PaymentController::class, 'createCheckout']);
    Route::post('/payment-status', [PaymentController::class, 'getPaymentStatus']);

    /* Apple Pay */
    Route::post('/applepay/initiate', [PaymentController::class, 'applePay']);
    Route::post('/applepay/verify', [PaymentController::class, 'verify']);

    /* Apple Pay */

    // HYPERPAY
    Route::group(['middleware' => 'isUser'], function () {
        Route::group(['prefix' => 'customer'], function () {
            // Pay Via Wallet
            Route::post('/PayViaWallet', [UserController::class, 'PayViaWallet']);
            Route::get('/myWallet', [UserController::class, 'myWallet']);
            // Pay Via Wallet

            // Bills
            Route::get('/bills', [BillController::class, 'index']);
            // Bills

            // User profile routes
            Route::get('/RequestedDataForCreateCheckout', [UserController::class, 'RequestedDataForCreateCheckout']);
            Route::get('/profile', [UserController::class, 'profile']);
            Route::post('/OTPforProfile', [UserController::class, 'generateOTPforUpdateProfile']);
            Route::post('/update_profile', [UserController::class, 'updateProfile']);
            Route::group(['prefix' => 'activities'], function () {
                Route::get('/showBookingDetails/{id}', [UserActivityController::class, 'showBookingDetails']);
                Route::post('/rate', [RatingController::class, 'store']);
                Route::get('/wishlist', [UserActivityController::class, 'wishlist']);
                Route::post('/wishlist/addOrdelete', [UserActivityController::class, 'addORdeletewishlist']);
            });

            Route::group(['prefix' => 'commercial-tools'], function () {
                Route::get('/wishlist', [UserCommercialToolsController::class, 'wishlist']);
                Route::post('/wishlist/addOrdelete', [UserCommercialToolsController::class, 'addORdeletewishlist']);
                Route::post('/rate', [RatingToolController::class, 'store']);
            });

            Route::group(['prefix' => 'booking'], function () {
                Route::get('/', [BookingController::class, 'index']);
                Route::post('/store', [BookingController::class, 'store']);
                Route::post('/wait_or_cancel', [BookingController::class, 'storeWaitingList']);
                // Route::get('/{tool}', [BookingController::class, 'show']);
            });
        });
    });
});

Route::group(['prefix' => 'customer'], function () {
    Route::get('/testttttHome', [TestController::class, 'index']);
    Route::post('/testEmail', [TestController::class, 'testEmail']);
    Route::post('/testEmailMailtip', [TestController::class, 'testEmailMailtip']);
    Route::group(['prefix' => 'offers'], function () {
        Route::get('/', [OfferController::class, 'index']);
        Route::get('/{offer}', [OfferController::class, 'show']);
    });

    Route::group(['prefix' => 'settings'], function () {
        Route::get('/{id}', [SettingsController::class, 'find']);
    });
    Route::group(['prefix' => 'faqs'], function () {
        Route::get('/', [FaqsController::class, 'index']);
        Route::get('/{id}', [FaqsController::class, 'find']);
    });

    Route::group(['prefix' => 'activities'], function () {
        Route::get('', [UserActivityController::class, 'index']);
        Route::get('/type', [UserActivityController::class, 'index_type']);
        Route::get('/adventures', [UserActivityController::class, 'activityTypes']);
        Route::get('/{activity}', [UserActivityController::class, 'show']);
        Route::get('/reviews/{activity_id}/{perPage}', [RatingController::class, 'show_reviews']);
        Route::post('/featured_trips', [UserActivityController::class, 'getfeatured_trips']);
    });

    Route::group(['prefix' => 'commercial-tools'], function () {
        Route::get('/', [UserCommercialToolsController::class, 'index']);
        Route::post('/forSale', [UserCommercialToolsController::class, 'forSale']);
        Route::post('/forRent', [UserCommercialToolsController::class, 'forRent']);
        Route::get('/{tool}', [UserCommercialToolsController::class, 'show']);
        Route::get('/reviews/{tool_id}/{perPage}', [RatingToolController::class, 'show_reviews']);

        Route::group(['prefix' => 'cart'], function () {
            // Add item to cart
            Route::post('/add', [CartController::class, 'addToCart']);
            // Update cart item quantity
            Route::put('/update', [CartController::class, 'updateCartItem']);
            // Remove an item from the cart
            Route::post('/remove', [CartController::class, 'removeFromCart']);
            // Get cart items
            Route::post('/', [CartController::class, 'showCart']);
            // Clear cart
            Route::delete('/clear', [CartController::class, 'clearCart']);
            // Checkout
            Route::post('/checkout', [CartController::class, 'checkout']);
        });
    });
    // Orders
    Route::group(['prefix' => 'orders'], function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/show/{order_id}', [OrderController::class, 'show']);
        Route::post('/add', [OrderController::class, 'addToOrderFromCart']);
        Route::get('/order-items/{id}', [OrderItemController::class, 'show']);
    });
    // Orders
});
// });
