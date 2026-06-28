import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\LockerRentalController::createOrder
* @see app/Http/Controllers/LockerRentalController.php:17
* @route '/api/create-order'
*/
export const createOrder = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: createOrder.url(options),
    method: 'post',
})

createOrder.definition = {
    methods: ["post"],
    url: '/api/create-order',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LockerRentalController::createOrder
* @see app/Http/Controllers/LockerRentalController.php:17
* @route '/api/create-order'
*/
createOrder.url = (options?: RouteQueryOptions) => {
    return createOrder.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LockerRentalController::createOrder
* @see app/Http/Controllers/LockerRentalController.php:17
* @route '/api/create-order'
*/
createOrder.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: createOrder.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LockerRentalController::createOrder
* @see app/Http/Controllers/LockerRentalController.php:17
* @route '/api/create-order'
*/
const createOrderForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: createOrder.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LockerRentalController::createOrder
* @see app/Http/Controllers/LockerRentalController.php:17
* @route '/api/create-order'
*/
createOrderForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: createOrder.url(options),
    method: 'post',
})

createOrder.form = createOrderForm

/**
* @see \App\Http\Controllers\LockerRentalController::pickup
* @see app/Http/Controllers/LockerRentalController.php:285
* @route '/api/pickup/{orderId}'
*/
export const pickup = (args: { orderId: string | number } | [orderId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pickup.url(args, options),
    method: 'get',
})

pickup.definition = {
    methods: ["get","head"],
    url: '/api/pickup/{orderId}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LockerRentalController::pickup
* @see app/Http/Controllers/LockerRentalController.php:285
* @route '/api/pickup/{orderId}'
*/
pickup.url = (args: { orderId: string | number } | [orderId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { orderId: args }
    }

    if (Array.isArray(args)) {
        args = {
            orderId: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        orderId: args.orderId,
    }

    return pickup.definition.url
            .replace('{orderId}', parsedArgs.orderId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\LockerRentalController::pickup
* @see app/Http/Controllers/LockerRentalController.php:285
* @route '/api/pickup/{orderId}'
*/
pickup.get = (args: { orderId: string | number } | [orderId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pickup.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LockerRentalController::pickup
* @see app/Http/Controllers/LockerRentalController.php:285
* @route '/api/pickup/{orderId}'
*/
pickup.head = (args: { orderId: string | number } | [orderId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: pickup.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LockerRentalController::pickup
* @see app/Http/Controllers/LockerRentalController.php:285
* @route '/api/pickup/{orderId}'
*/
const pickupForm = (args: { orderId: string | number } | [orderId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: pickup.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LockerRentalController::pickup
* @see app/Http/Controllers/LockerRentalController.php:285
* @route '/api/pickup/{orderId}'
*/
pickupForm.get = (args: { orderId: string | number } | [orderId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: pickup.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LockerRentalController::pickup
* @see app/Http/Controllers/LockerRentalController.php:285
* @route '/api/pickup/{orderId}'
*/
pickupForm.head = (args: { orderId: string | number } | [orderId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: pickup.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

pickup.form = pickupForm

/**
* @see \App\Http\Controllers\LockerRentalController::paymentCallback
* @see app/Http/Controllers/LockerRentalController.php:173
* @route '/api/webhook'
*/
export const paymentCallback = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: paymentCallback.url(options),
    method: 'post',
})

paymentCallback.definition = {
    methods: ["post"],
    url: '/api/webhook',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LockerRentalController::paymentCallback
* @see app/Http/Controllers/LockerRentalController.php:173
* @route '/api/webhook'
*/
paymentCallback.url = (options?: RouteQueryOptions) => {
    return paymentCallback.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LockerRentalController::paymentCallback
* @see app/Http/Controllers/LockerRentalController.php:173
* @route '/api/webhook'
*/
paymentCallback.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: paymentCallback.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LockerRentalController::paymentCallback
* @see app/Http/Controllers/LockerRentalController.php:173
* @route '/api/webhook'
*/
const paymentCallbackForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: paymentCallback.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LockerRentalController::paymentCallback
* @see app/Http/Controllers/LockerRentalController.php:173
* @route '/api/webhook'
*/
paymentCallbackForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: paymentCallback.url(options),
    method: 'post',
})

paymentCallback.form = paymentCallbackForm

/**
* @see \App\Http\Controllers\LockerRentalController::arduinoTap
* @see app/Http/Controllers/LockerRentalController.php:420
* @route '/api/arduino/tap-card'
*/
export const arduinoTap = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: arduinoTap.url(options),
    method: 'post',
})

arduinoTap.definition = {
    methods: ["post"],
    url: '/api/arduino/tap-card',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LockerRentalController::arduinoTap
* @see app/Http/Controllers/LockerRentalController.php:420
* @route '/api/arduino/tap-card'
*/
arduinoTap.url = (options?: RouteQueryOptions) => {
    return arduinoTap.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LockerRentalController::arduinoTap
* @see app/Http/Controllers/LockerRentalController.php:420
* @route '/api/arduino/tap-card'
*/
arduinoTap.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: arduinoTap.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LockerRentalController::arduinoTap
* @see app/Http/Controllers/LockerRentalController.php:420
* @route '/api/arduino/tap-card'
*/
const arduinoTapForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: arduinoTap.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LockerRentalController::arduinoTap
* @see app/Http/Controllers/LockerRentalController.php:420
* @route '/api/arduino/tap-card'
*/
arduinoTapForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: arduinoTap.url(options),
    method: 'post',
})

arduinoTap.form = arduinoTapForm

const locker = {
    createOrder: Object.assign(createOrder, createOrder),
    pickup: Object.assign(pickup, pickup),
    paymentCallback: Object.assign(paymentCallback, paymentCallback),
    arduinoTap: Object.assign(arduinoTap, arduinoTap),
}

export default locker