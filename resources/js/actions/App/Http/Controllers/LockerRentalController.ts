import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
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
* @see \App\Http\Controllers\LockerRentalController::getPickupCode
* @see app/Http/Controllers/LockerRentalController.php:287
* @route '/api/pickup/{orderId}'
*/
export const getPickupCode = (args: { orderId: string | number } | [orderId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getPickupCode.url(args, options),
    method: 'get',
})

getPickupCode.definition = {
    methods: ["get","head"],
    url: '/api/pickup/{orderId}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LockerRentalController::getPickupCode
* @see app/Http/Controllers/LockerRentalController.php:287
* @route '/api/pickup/{orderId}'
*/
getPickupCode.url = (args: { orderId: string | number } | [orderId: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return getPickupCode.definition.url
            .replace('{orderId}', parsedArgs.orderId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\LockerRentalController::getPickupCode
* @see app/Http/Controllers/LockerRentalController.php:287
* @route '/api/pickup/{orderId}'
*/
getPickupCode.get = (args: { orderId: string | number } | [orderId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getPickupCode.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LockerRentalController::getPickupCode
* @see app/Http/Controllers/LockerRentalController.php:287
* @route '/api/pickup/{orderId}'
*/
getPickupCode.head = (args: { orderId: string | number } | [orderId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getPickupCode.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LockerRentalController::getPickupCode
* @see app/Http/Controllers/LockerRentalController.php:287
* @route '/api/pickup/{orderId}'
*/
const getPickupCodeForm = (args: { orderId: string | number } | [orderId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getPickupCode.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LockerRentalController::getPickupCode
* @see app/Http/Controllers/LockerRentalController.php:287
* @route '/api/pickup/{orderId}'
*/
getPickupCodeForm.get = (args: { orderId: string | number } | [orderId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getPickupCode.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LockerRentalController::getPickupCode
* @see app/Http/Controllers/LockerRentalController.php:287
* @route '/api/pickup/{orderId}'
*/
getPickupCodeForm.head = (args: { orderId: string | number } | [orderId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getPickupCode.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

getPickupCode.form = getPickupCodeForm

/**
* @see \App\Http\Controllers\LockerRentalController::handleNotification
* @see app/Http/Controllers/LockerRentalController.php:174
* @route '/api/webhook'
*/
export const handleNotification = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: handleNotification.url(options),
    method: 'post',
})

handleNotification.definition = {
    methods: ["post"],
    url: '/api/webhook',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LockerRentalController::handleNotification
* @see app/Http/Controllers/LockerRentalController.php:174
* @route '/api/webhook'
*/
handleNotification.url = (options?: RouteQueryOptions) => {
    return handleNotification.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LockerRentalController::handleNotification
* @see app/Http/Controllers/LockerRentalController.php:174
* @route '/api/webhook'
*/
handleNotification.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: handleNotification.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LockerRentalController::handleNotification
* @see app/Http/Controllers/LockerRentalController.php:174
* @route '/api/webhook'
*/
const handleNotificationForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: handleNotification.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LockerRentalController::handleNotification
* @see app/Http/Controllers/LockerRentalController.php:174
* @route '/api/webhook'
*/
handleNotificationForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: handleNotification.url(options),
    method: 'post',
})

handleNotification.form = handleNotificationForm

const LockerRentalController = { createOrder, getPickupCode, handleNotification }

export default LockerRentalController