# CHANGELOG FOR PAYMENTSCHEDULE MODULE

## Not Released



## Release 1.3
- FIX : Compat v21 - *10/12/2024* - 1.3.2
- FIX : Le PDF de l'echéancier était abs lors de l'appplication d'un modèle de mail . - **24/12/2024** - 1.3.1
- FIX: Compat v20  
  Changed Dolibarr compatibility range to 16 min - 20 max - *28/07/2024* - 1.3.0

## Release 1.2
- FIX : Champ échéancier vide lors de la saisie d'un réglement - *16/07/2024* - 1.2.3
- FIX refused prelevement - *10/06/2023* - 1.2.2
- FIX missing compat - *11/04/2023* - 1.2.1
- NEW : Compat v19 - *15/12/2023* - 1.2.0
  - DOL v15 minimum 
  - PHP 7 min 

## Release 1.1

- FIX : Compat v18 - *19/07/2022* - 1.1.7
- FIX : Compat v18 - *14/06/2022* - 1.1.6
- FIX : the scheduledet must be rejectable at anytime - *26/04/2022* - 1.1.5
- FIX : Fix fatal on reject witdrawal + misMerge... - *07/01/2022* - 1.1.4
- FIX : sql compatibility for list in V14  - *23/11/2021* - 1.1.3  
  In V14 column total and tva are renamed in `total_ht` and `total_tva`
  for `llx_facture` table
- FIX : Backward compatibility  - *12/10/2021* - 1.1.2
- FIX : Get payment table for payment link to just one invoice - *07/09/2021* - 1.1.1
- NEW : compatibility with Dolibarr v13-v14 - *03/08/2021* - 1.1.0

## Release 1.0

- FIX : We must not test TVA on subtotal lines - *10/02/2022* - 1.0.5
- FIX : global $langs to avoid generate PDF in english *22/10/2021* - 1.0.4
- FIX : Add `CREDIT_TRANSFER_ORDER_CREATE` trigger to create bank direct debit - 1.0.3 - *09/04/2021* 
