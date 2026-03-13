# Extensia Newsman pentru OpenCart 3 - Ghid de Configurare

Acest ghid prezinta toate setarile din extensia Newsman pentru OpenCart 3, pentru a va putea conecta magazinul la contul Newsman si a incepe sa colectati abonati, sa trimiteti newslettere si sa urmariti comportamentul clientilor.

---

## Unde Gasiti Setarile Extensiei

Dupa instalarea extensiei, veti gasi setarile Newsman in doua locuri:

- **Admin > Extensions > Modules > NewsMAN** (butonul Edit) - Setari principale: conexiune API, sincronizare abonati, newsletter la checkout si setari dezvoltatori
- **Admin > Extensions > Analytics > NewsMAN Remarketing** (butonul Edit) - Pixel de remarketing si setari de urmarire vizitatori

---

## Primii Pasi - Conectarea la Newsman

Inainte de a putea folosi orice functionalitate, trebuie sa conectati extensia la contul dvs. Newsman. Exista doua modalitati:

### Optiunea A: Configurare Rapida cu OAuth (Recomandat)

1. Accesati **Admin > Extensions > Modules > NewsMAN** si faceti click pe **Edit**.
2. Faceti click pe butonul **Login with NewsMAN**.
3. Veti fi redirectionat catre site-ul Newsman. Autentificati-va daca este necesar si acordati acces.
4. Veti fi redirectionat inapoi catre o pagina in OpenCart unde alegeti lista de email dintr-un dropdown. Selectati lista pe care doriti sa o folositi si faceti click pe **Save**.
5. Asta e tot - API Key, User ID, Lista si Remarketing ID sunt toate configurate.

### Optiunea B: Configurare Manuala

1. Autentificati-va in contul Newsman pe newsman.app.
2. Accesati setarile contului si copiati **API Key** si **User ID**.
3. In OpenCart, accesati **Admin > Extensions > Modules > NewsMAN** si faceti click pe **Edit**.
4. Introduceti **User ID** si **API Key** in campurile corespunzatoare.
5. Selectati **Lista** din dropdown. Listele sunt preluate din Newsman folosind credentialele introduse.
6. Optional, selectati un **Segment**.
7. Faceti click pe **Save**.

---

## Reconfigurare cu Newsman OAuth

Daca trebuie sa reconectati extensia la un alt cont Newsman, sau daca credentialele s-au schimbat, accesati pagina principala de setari Newsman si faceti click pe butonul **Reconfigure with Newsman Login**. Acest lucru va va ghida prin acelasi flux OAuth descris mai sus - veti fi redirectionat catre site-ul Newsman pentru a autoriza accesul, apoi inapoi in OpenCart pentru a selecta lista de email. API Key, User ID, Lista si Remarketing ID vor fi actualizate cu noile credentiale.

---

## Pagina Setari Principale

Accesati **Admin > Extensions > Modules > NewsMAN > Edit** pentru a configura comportamentul de baza al extensiei.

### Setari de Conexiune

- **Module Status** - Activeaza sau dezactiveaza modulul Newsman. Cand este dezactivat, toate functiile Newsman sunt inactive.

- **User ID** - User ID-ul dvs. Newsman. Se completeaza automat daca ati folosit OAuth.

- **API Key** - API Key-ul dvs. Newsman. Se completeaza automat daca ati folosit OAuth.

- **List** - Selectati lista de email Newsman care va primi abonatii dvs. Dropdown-ul afiseaza toate listele de email din contul dvs. Newsman (listele SMS sunt excluse).

- **Segment** - Optional, selectati un segment din lista aleasa. Segmentele va permit sa organizati abonatii in grupuri. Daca nu folositi segmente, lasati acest camp gol.

### Setari Newsletter

- **Double Opt-in** - Cand este activat (valoarea implicita), noii abonati primesc un email de confirmare si trebuie sa faca click pe un link pentru a-si confirma abonarea. Aceasta optiune este recomandata pentru conformitatea GDPR. Cand este dezactivat, abonatii sunt adaugati imediat in lista.

- **Send User IP Address** - Cand este activat, adresa IP a vizitatorului este trimisa catre Newsman cand se aboneaza sau dezaboneaza. Acest lucru poate ajuta la analiza si conformitate. Cand este dezactivat, se trimite in schimb adresa **Server IP**.

- **Server IP** - O adresa IP de rezerva folosita cand "Send User IP Address" este dezactivat. De obicei puteti lasa acest camp gol.

### Autorizare Export

- **Export Authorize Header Name / Key** - Aceasta este o optiune veche (legacy) pentru protejarea exporturilor de date cu credentiale de securitate personalizate. Daca v-ati conectat prin OAuth, nu trebuie sa le setati - extensia gestioneaza autentificarea automat. Trebuie sa le completati doar daca ati configurat conexiunea manual si doriti sa adaugati un nivel suplimentar de securitate la exporturile de date.

### Newsletter la Checkout

Aceste setari adauga un checkbox de newsletter pe pagina de checkout a magazinului, astfel incat clientii se pot abona in timp ce plaseaza o comanda.

- **Enable Newsletter Checkbox in Checkout** - Activati pentru a afisa un checkbox de abonare pe pagina de checkout. Activat implicit.

- **Newsletter Checkbox Marked by Default** - Daca este activat, checkbox-ul va fi pre-bifat. Clientii vor trebui sa il debifeze daca nu doresc sa se aboneze. Nota: in unele regiuni, pre-bifarea poate sa nu fie conforma cu reglementarile de confidentialitate.

- **Newsletter Checkbox Label** - Personalizati textul afisat langa checkbox. Valoarea implicita este "I wish to subscribe to the newsletter".

### Optiuni Multi-Magazin

Aceste setari sunt vizibile doar daca aveti mai multe magazine configurate in OpenCart.

- **Export Subscribers by Store** - Cand este activat, doar abonatii care apartin magazinului curent sunt exportati catre Newsman. Dezactivat implicit.

- **Export Customers by Store** - Cand este activat, doar clientii asociati magazinului curent sunt exportati. Clientii din OpenCart se pot autentifica in toate magazinele indiferent de locul in care au fost creati, deci activati aceasta optiune daca doriti sa ii filtrati pe magazine. Activat implicit.

### Setari pentru Dezvoltatori

Aceste setari sunt destinate utilizatorilor avansati si dezvoltatorilor. In cele mai multe cazuri, ar trebui sa le lasati la valorile implicite.

- **Log Level** - Controleaza cat de mult detaliu scrie extensia in fisierul de log. Valoarea implicita este **Error**, care inregistreaza doar problemele. Setati la **Debug** daca investigati o problema (dar nu uitati sa il setati inapoi dupa aceea, deoarece modul Debug creeaza fisiere de log mari).

- **Log Clean Days** - Sterge automat fisierele de log mai vechi decat acest numar de zile. Valoarea implicita este de 60 de zile.

- **API Timeout** - Cate secunde asteapta extensia un raspuns de la Newsman inainte de a renunta. Valoarea implicita de 10 secunde functioneaza bine pentru majoritatea configuratiilor.

- **Enable Test User IP / Test IP** - Doar pentru dezvoltare si testare. Va permite sa simulati o adresa IP specifica de vizitator. Aceasta optiune nu ar trebui activata intr-un mediu de productie.

---

## Setari Remarketing

Accesati **Admin > Extensions > Analytics > NewsMAN Remarketing > Edit** pentru a configura urmarirea vizitatorilor.

Remarketing-ul permite Newsman sa urmareasca ce pagini si produse vizualizeaza vizitatorii dvs., astfel incat sa le puteti trimite emailuri personalizate (de ex., reamintiri de cos abandonat, recomandari de produse).

- **Status** - Activeaza sau dezactiveaza pixelul de remarketing pe magazinul dvs. Activat implicit.

- **NewsMAN Remarketing ID** - Acesta identifica magazinul dvs. in sistemul de urmarire Newsman. Se completeaza automat daca ati folosit OAuth. Il puteti gasi si in contul Newsman la setarile de remarketing.

- **Anonymize IP Address** - Cand este activat, adresele IP ale vizitatorilor sunt anonimizate inainte de a fi trimise catre Newsman. Recomandat pentru conformitatea GDPR. Activat implicit.

- **Send Phone Number** - Include numerele de telefon ale clientilor in datele de remarketing. Se aplica doar clientilor autentificati care au furnizat un numar de telefon. Activat implicit.

### Ce se Urmareste

Pixelul de remarketing urmareste automat activitatea vizitatorilor pe magazinul dvs.:

- **Pagini de produs** - Inregistreaza ce produse vizualizeaza vizitatorii
- **Pagini de categorie** - Inregistreaza ce categorii navigheaza vizitatorii
- **Cos de cumparaturi** - Inregistreaza continutul si valoarea cosului
- **Confirmare comanda** - Inregistreaza achizitiile finalizate cu valoarea si articolele comenzii
- **Toate celelalte pagini** - Urmarire generala a vizualizarilor de pagina

---

## Intrebari Frecvente

### Cum stiu daca conexiunea functioneaza?

Dupa introducerea credentialelor si salvare, verificati ca dropdown-ul **List** afiseaza listele dvs. Newsman. Fiecare cont Newsman are cel putin o lista implicit, deci daca credentialele sunt corecte, listele vor aparea.

### Ce este Double Opt-in?

Cand Double Opt-in este activat, noii abonati primesc un email de confirmare cu un link pe care trebuie sa faca click pentru a-si confirma abonarea. Aceasta asigura ca adresa de email este valida si ca persoana chiar doreste sa se aboneze. Double Opt-in este recomandat pentru conformitatea GDPR.

### Unde sunt logurile extensiei?

Extensia scrie loguri in fisierele `storage/logs/newsman_*.log`. Nivelul de logare este controlat din Setarile pentru Dezvoltatori. Fisierele de log mai vechi decat numarul de zile configurat (implicit: 60) sunt curatate automat.

### Pot configura liste diferite pentru magazine diferite?

Da. Toate setarile suporta sistemul multi-magazin al OpenCart. Configurati liste, segmente sau ID-uri de remarketing diferite pentru fiecare magazin.

### Ce se intampla cand un client se aboneaza la newsletter?

Cand un client se aboneaza prin checkbox-ul de checkout, inregistrarea contului sau pagina de setari a contului, extensia trimite automat abonarea catre Newsman folosind lista si segmentul configurate. Daca Double Opt-in este activat, Newsman va trimite mai intai un email de confirmare.
