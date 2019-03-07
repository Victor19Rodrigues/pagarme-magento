Validation.add('validate-card-expiration-date', 'Please enter a valid expiration date. For example 12 / 25', function(v) {
    return /\d{2}\s?\/?\s?\d{2}/i.test(v)
})
