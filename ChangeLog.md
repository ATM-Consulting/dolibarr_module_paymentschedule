# CHANGELOG FOR PAYMENTSCHEDULE MODULE

## Not Released



## Version 1.1
- FIX : Compat v18 - *14/06/2022* - 1.1.6
- FIX : the scheduledet must be rejectable at anytime - *26/04/2022* - 1.1.5
- FIX : Fix fatal on reject witdrawal + misMerge... - *07/01/2022* - 1.1.4
- FIX : sql compatibility for list in V14  - *23/11/2021* - 1.1.3  
  In V14 column total and tva are renamed in `total_ht` and `total_tva`
  for `llx_facture` table
- FIX : Backward compatibility  - *12/10/2021* - 1.1.2
- FIX : Get payment table for payment link to just one invoice - *07/09/2021* - 1.1.1
- NEW : compatibility with Dolibarr v13-v14 - *03/08/2021* - 1.1.0

## Version 1.0

- FIX : We must not test TVA on subtotal lines - *10/02/2022* - 1.0.5
- FIX : global $langs to avoid generate PDF in english *22/10/2021* - 1.0.4
- FIX : Add `CREDIT_TRANSFER_ORDER_CREATE` trigger to create bank direct debit - 1.0.3 - *09/04/2021* 
