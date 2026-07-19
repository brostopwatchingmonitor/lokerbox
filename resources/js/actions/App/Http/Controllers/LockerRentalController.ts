import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\LockerRentalController::createOrder
* @see app/Http/Controllers/LockerRentalController.php:28
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
* @see app/Http/Controllers/LockerRentalController.php:28
* @route '/api/create-order'
*/
createOrder.url = (options?: RouteQueryOptions) => {
    return createOrder.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LockerRentalController::createOrder
* @see app/Http/Controllers/LockerRentalController.php:28
* @route '/api/create-order'
*/
createOrder.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: createOrder.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LockerRentalController::createOrder
* @see app/Http/Controllers/LockerRentalController.php:28
* @route '/api/create-order'
*/
const createOrderForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: createOrder.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LockerRentalController::createOrder
* @see app/Http/Controllers/LockerRentalController.php:28
* @route '/api/create-order'
*/
createOrderForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: createOrder.url(options),
    method: 'post',
})

createOrder.form = createOrderForm

/**
* @see \App\Http\Controllers\LockerRentalController::getPickupCode
* @see app/Http/Controllers/LockerRentalController.php:81
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
* @see app/Http/Controllers/LockerRentalController.php:81
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
* @see app/Http/Controllers/LockerRentalController.php:81
* @route '/api/pickup/{orderId}'
*/
getPickupCode.get = (args: { orderId: string | number } | [orderId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getPickupCode.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LockerRentalController::getPickupCode
* @see app/Http/Controllers/LockerRentalController.php:81
* @route '/api/pickup/{orderId}'
*/
getPickupCode.head = (args: { orderId: string | number } | [orderId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getPickupCode.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LockerRentalController::getPickupCode
* @see app/Http/Controllers/LockerRentalController.php:81
* @route '/api/pickup/{orderId}'
*/
const getPickupCodeForm = (args: { orderId: string | number } | [orderId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getPickupCode.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LockerRentalController::getPickupCode
* @see app/Http/Controllers/LockerRentalController.php:81
* @route '/api/pickup/{orderId}'
*/
getPickupCodeForm.get = (args: { orderId: string | number } | [orderId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getPickupCode.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LockerRentalController::getPickupCode
* @see app/Http/Controllers/LockerRentalController.php:81
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
* @see \App\Http\Controllers\LockerRentalController::registerCard
* @see app/Http/Controllers/LockerRentalController.php:90
* @route '/api/register-card'
*/
export const registerCard = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: registerCard.url(options),
    method: 'post',
})

registerCard.definition = {
    methods: ["post"],
    url: '/api/register-card',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LockerRentalController::registerCard
* @see app/Http/Controllers/LockerRentalController.php:90
* @route '/api/register-card'
*/
registerCard.url = (options?: RouteQueryOptions) => {
    return registerCard.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LockerRentalController::registerCard
* @see app/Http/Controllers/LockerRentalController.php:90
* @route '/api/register-card'
*/
registerCard.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: registerCard.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LockerRentalController::registerCard
* @see app/Http/Controllers/LockerRentalController.php:90
* @route '/api/register-card'
*/
const registerCardForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: registerCard.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LockerRentalController::registerCard
* @see app/Http/Controllers/LockerRentalController.php:90
* @route '/api/register-card'
*/
registerCardForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: registerCard.url(options),
    method: 'post',
})

registerCard.form = registerCardForm

/**
* @see \App\Http\Controllers\LockerRentalController::getHistory
* @see app/Http/Controllers/LockerRentalController.php:132
* @route '/api/rentals/history'
*/
export const getHistory = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getHistory.url(options),
    method: 'get',
})

getHistory.definition = {
    methods: ["get","head"],
    url: '/api/rentals/history',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LockerRentalController::getHistory
* @see app/Http/Controllers/LockerRentalController.php:132
* @route '/api/rentals/history'
*/
getHistory.url = (options?: RouteQueryOptions) => {
    return getHistory.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LockerRentalController::getHistory
* @see app/Http/Controllers/LockerRentalController.php:132
* @route '/api/rentals/history'
*/
getHistory.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getHistory.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LockerRentalController::getHistory
* @see app/Http/Controllers/LockerRentalController.php:132
* @route '/api/rentals/history'
*/
getHistory.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getHistory.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LockerRentalController::getHistory
* @see app/Http/Controllers/LockerRentalController.php:132
* @route '/api/rentals/history'
*/
const getHistoryForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getHistory.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LockerRentalController::getHistory
* @see app/Http/Controllers/LockerRentalController.php:132
* @route '/api/rentals/history'
*/
getHistoryForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getHistory.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LockerRentalController::getHistory
* @see app/Http/Controllers/LockerRentalController.php:132
* @route '/api/rentals/history'
*/
getHistoryForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getHistory.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

getHistory.form = getHistoryForm

/**
* @see \App\Http\Controllers\LockerRentalController::reopenLocker
* @see app/Http/Controllers/LockerRentalController.php:149
* @route '/api/rentals/reopen'
*/
export const reopenLocker = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reopenLocker.url(options),
    method: 'post',
})

reopenLocker.definition = {
    methods: ["post"],
    url: '/api/rentals/reopen',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LockerRentalController::reopenLocker
* @see app/Http/Controllers/LockerRentalController.php:149
* @route '/api/rentals/reopen'
*/
reopenLocker.url = (options?: RouteQueryOptions) => {
    return reopenLocker.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LockerRentalController::reopenLocker
* @see app/Http/Controllers/LockerRentalController.php:149
* @route '/api/rentals/reopen'
*/
reopenLocker.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reopenLocker.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LockerRentalController::reopenLocker
* @see app/Http/Controllers/LockerRentalController.php:149
* @route '/api/rentals/reopen'
*/
const reopenLockerForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reopenLocker.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LockerRentalController::reopenLocker
* @see app/Http/Controllers/LockerRentalController.php:149
* @route '/api/rentals/reopen'
*/
reopenLockerForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reopenLocker.url(options),
    method: 'post',
})

reopenLocker.form = reopenLockerForm

/**
* @see \App\Http\Controllers\LockerRentalController::handleNotification
* @see app/Http/Controllers/LockerRentalController.php:61
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
* @see app/Http/Controllers/LockerRentalController.php:61
* @route '/api/webhook'
*/
handleNotification.url = (options?: RouteQueryOptions) => {
    return handleNotification.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LockerRentalController::handleNotification
* @see app/Http/Controllers/LockerRentalController.php:61
* @route '/api/webhook'
*/
handleNotification.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: handleNotification.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LockerRentalController::handleNotification
* @see app/Http/Controllers/LockerRentalController.php:61
* @route '/api/webhook'
*/
const handleNotificationForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: handleNotification.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LockerRentalController::handleNotification
* @see app/Http/Controllers/LockerRentalController.php:61
* @route '/api/webhook'
*/
handleNotificationForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: handleNotification.url(options),
    method: 'post',
})

handleNotification.form = handleNotificationForm

/**
* @see \App\Http\Controllers\LockerRentalController::tapCard
* @see app/Http/Controllers/LockerRentalController.php:112
* @route '/api/arduino/tap-card'
*/
export const tapCard = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: tapCard.url(options),
    method: 'post',
})

tapCard.definition = {
    methods: ["post"],
    url: '/api/arduino/tap-card',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LockerRentalController::tapCard
* @see app/Http/Controllers/LockerRentalController.php:112
* @route '/api/arduino/tap-card'
*/
tapCard.url = (options?: RouteQueryOptions) => {
    return tapCard.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LockerRentalController::tapCard
* @see app/Http/Controllers/LockerRentalController.php:112
* @route '/api/arduino/tap-card'
*/
tapCard.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: tapCard.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LockerRentalController::tapCard
* @see app/Http/Controllers/LockerRentalController.php:112
* @route '/api/arduino/tap-card'
*/
const tapCardForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: tapCard.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LockerRentalController::tapCard
* @see app/Http/Controllers/LockerRentalController.php:112
* @route '/api/arduino/tap-card'
*/
tapCardForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: tapCard.url(options),
    method: 'post',
})

tapCard.form = tapCardForm

const LockerRentalController = { createOrder, getPickupCode, registerCard, getHistory, reopenLocker, handleNotification, tapCard }

export default LockerRentalController