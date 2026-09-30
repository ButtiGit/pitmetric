window.GameDevData = (() => {
    'use strict';

    const tracks = {
        Melbourne:'M206 405 C137 329 151 211 256 175 C338 147 388 111 487 129 C583 146 594 208 680 207 C789 207 851 280 809 351 C777 405 707 395 638 438 C556 489 434 484 350 457 C289 437 249 451 206 405 Z',
        Shanghai:'M193 403 C126 330 153 225 239 207 C299 195 336 215 355 173 C382 112 490 102 526 164 C558 219 529 267 484 288 C443 308 441 347 491 361 C551 378 593 338 625 283 C662 220 764 206 818 267 C869 324 825 421 745 430 C660 440 605 493 492 480 C384 468 270 484 193 403 Z',
        Suzuka:'M175 313 C196 213 303 147 407 163 C484 175 527 228 574 254 C627 284 725 240 803 185 C767 269 674 321 579 326 C495 331 426 300 364 322 C301 344 245 407 201 449 C252 423 331 404 397 415 C482 428 544 486 638 460 C723 437 775 371 805 316 C720 352 641 375 558 363 C474 351 413 289 334 277 C267 267 217 287 175 313 Z',
        Sakhir:'M186 384 L227 205 L388 143 L478 205 L574 151 L741 184 L815 284 L737 340 L792 433 L622 474 L535 408 L420 469 L289 429 Z',
        Jeddah:'M257 491 C206 412 195 313 227 218 C251 147 326 105 395 131 C445 150 481 186 548 163 C631 133 722 142 778 206 C830 267 805 347 753 407 C691 480 615 500 536 462 C459 424 383 450 326 485 C298 502 275 507 257 491 Z',
        Miami:'M183 346 C153 263 209 178 305 172 L455 172 C511 173 533 126 591 139 L784 181 L820 280 L758 332 L797 419 L681 465 L561 427 L472 477 L328 461 L227 416 Z',
        Montreal:'M231 456 C183 393 182 300 213 238 L279 113 L355 169 L462 136 L518 199 L621 176 L743 213 L808 313 L739 390 L654 365 L588 451 L459 475 L354 425 Z',
        Monaco:'M210 405 L176 301 L228 206 L340 191 L399 123 L499 157 L531 226 L641 214 L782 278 L805 366 L731 444 L634 416 L553 480 L418 452 L305 476 Z',
        Barcelona:'M164 337 C153 234 239 152 344 148 L510 146 L575 198 L704 173 L822 231 L803 327 L727 337 L701 422 L575 467 L461 438 L366 480 L247 440 L177 392 Z',
        Spielberg:'M214 415 L174 291 L241 177 L393 132 L540 181 L692 153 L818 236 L773 330 L673 344 L624 439 L478 472 L337 443 Z',
        Silverstone:'M183 361 L215 202 L352 161 L438 224 L524 143 L665 177 L707 258 L817 286 L762 410 L626 428 L548 482 L421 437 L290 469 Z',
        Spa:'M151 341 C169 216 270 172 360 181 L441 128 L536 169 L601 247 L702 206 L837 258 L791 354 L701 364 L637 462 L510 439 L402 482 L305 421 L204 437 Z',
        Budapest:'M181 355 C163 260 224 173 322 155 C405 140 474 191 545 164 C638 128 749 172 803 254 C856 334 796 439 704 458 C608 478 555 430 469 456 C381 483 289 454 225 407 Z',
        Zandvoort:'M238 477 C148 411 148 279 197 190 C241 112 357 99 427 152 C482 194 517 170 568 137 C643 90 748 133 796 218 C847 309 819 424 726 466 C637 506 576 441 493 454 C394 470 319 536 238 477 Z',
        Monza:'M218 470 L172 339 L203 162 L313 125 L369 199 L511 187 L571 129 L753 158 L825 282 L775 405 L642 452 L557 399 L421 481 L313 427 Z',
        Madrid:'M177 353 C142 247 211 147 331 137 L455 133 C533 128 584 175 652 155 C730 132 820 194 828 284 C836 375 758 451 670 451 C586 451 540 412 466 444 C371 485 260 474 198 408 Z',
        Baku:'M171 402 L172 247 L270 172 L417 173 L417 111 L492 111 L493 254 L615 254 L615 191 L744 191 L816 278 L779 401 L672 459 L553 412 L439 482 L306 456 Z',
        Singapore:'M169 383 L193 204 L307 156 L399 202 L476 126 L575 173 L672 139 L821 224 L786 323 L835 394 L715 466 L620 418 L534 474 L432 429 L328 475 L229 442 Z',
        Austin:'M161 337 L198 190 L320 129 L423 197 L520 151 L612 215 L721 158 L825 240 L778 341 L820 413 L688 461 L585 416 L489 481 L387 423 L267 472 L190 413 Z',
        MexicoCity:'M183 414 L160 289 L229 171 L361 151 L444 203 L553 151 L693 178 L812 255 L775 344 L821 420 L690 459 L596 416 L486 476 L368 438 L273 474 Z',
        SaoPaulo:'M239 477 C151 426 144 309 181 218 C220 123 326 99 409 154 C475 198 516 195 581 156 C667 104 780 139 819 229 C853 308 812 399 743 437 C674 474 605 444 536 457 C422 479 334 531 239 477 Z',
        LasVegas:'M186 429 L175 243 L294 196 L331 111 L407 111 L422 272 L657 272 L678 139 L758 139 L816 276 L768 444 L620 463 L545 404 L407 472 L290 446 Z',
        Lusail:'M210 447 C142 375 160 260 215 188 C270 116 387 105 463 149 C528 187 562 153 621 131 C716 96 806 164 832 253 C860 350 796 441 701 462 C612 482 559 436 481 454 C389 475 284 524 210 447 Z',
        YasMarina:'M184 391 L172 247 L264 153 L397 160 L462 112 L560 152 L648 128 L813 222 L785 323 L829 400 L709 473 L594 434 L489 478 L380 432 L270 467 Z'
    };

    const f1Teams = [
        {name:'Mercedes',short:'MER',color:'#00d2be',perf:96,drivers:['George Russell','Kimi Antonelli']},
        {name:'Ferrari',short:'FER',color:'#ff2800',perf:94,drivers:['Charles Leclerc','Lewis Hamilton']},
        {name:'McLaren',short:'MCL',color:'#ff8700',perf:94,drivers:['Lando Norris','Oscar Piastri']},
        {name:'Red Bull Racing',short:'RBR',color:'#4f65ff',perf:92,drivers:['Max Verstappen','Isack Hadjar']},
        {name:'Aston Martin',short:'AMR',color:'#229971',perf:87,drivers:['Fernando Alonso','Lance Stroll']},
        {name:'Williams',short:'WIL',color:'#52a9ff',perf:86,drivers:['Carlos Sainz','Alexander Albon']},
        {name:'Audi',short:'AUD',color:'#ededed',perf:84,drivers:['Nico Hulkenberg','Gabriel Bortoleto']},
        {name:'Racing Bulls',short:'VCB',color:'#76c7ff',perf:83,drivers:['Liam Lawson','Arvid Lindblad']},
        {name:'Haas',short:'HAS',color:'#d6d6d6',perf:81,drivers:['Esteban Ocon','Oliver Bearman']},
        {name:'Alpine',short:'ALP',color:'#ff73c9',perf:80,drivers:['Pierre Gasly','Franco Colapinto']},
        {name:'Cadillac',short:'CAD',color:'#d9bf77',perf:79,drivers:['Sergio Perez','Valtteri Bottas']}
    ];

    const f2Teams = [
        {name:'Invicta Racing',short:'INV',color:'#dcb900',perf:92,drivers:['Rafael Camara','Joshua Duerksen']},
        {name:'Hitech',short:'HIT',color:'#b9b9b9',perf:90,drivers:['Ritomo Miyata','Colton Herta']},
        {name:'Campos Racing',short:'CAM',color:'#ffcf2f',perf:89,drivers:['Noel Leon','Nikola Tsolov']},
        {name:'DAMS Lucas Oil',short:'DAM',color:'#4b7aff',perf:88,drivers:['Dino Beganovic','Roman Bilinski']},
        {name:'MP Motorsport',short:'MP',color:'#ff6c3a',perf:87,drivers:['Gabriele Mini','Oliver Goethe']},
        {name:'PREMA Racing',short:'PRE',color:'#e34b5b',perf:87,drivers:['Sebastian Montoya','Mari Boya']},
        {name:'Rodin Motorsport',short:'ROD',color:'#c2ff36',perf:85,drivers:['Martinius Stenshorne','Alexander Dunne']},
        {name:'ART Grand Prix',short:'ART',color:'#ed4545',perf:85,drivers:['Kush Maini','Tasanapol Inthraphuvasak']},
        {name:'AIX Racing',short:'AIX',color:'#8d61ff',perf:82,drivers:['Emerson Fittipaldi','Cian Shields']},
        {name:'Van Amersfoort Racing',short:'VAR',color:'#f47932',perf:81,drivers:['Hiyu Yamakoshi','Rafael Villagomez']},
        {name:'TRIDENT',short:'TRI',color:'#4b93cf',perf:80,drivers:['Laurens van Hoepen','John Bennett']}
    ];

    const f1CalendarRaw = [
        ['Australian GP','Melbourne','6-8 Mar',58,false],['Chinese GP','Shanghai','13-15 Mar',56,true],['Japanese GP','Suzuka','27-29 Mar',53,false],['Bahrain GP','Sakhir','10-12 Apr',57,false],['Saudi Arabian GP','Jeddah','17-19 Apr',50,false],['Miami GP','Miami','1-3 May',57,true],['Canadian GP','Montreal','22-24 May',70,true],['Monaco GP','Monaco','5-7 Jun',78,false],['Spanish GP','Barcelona','12-14 Jun',66,false],['Austrian GP','Spielberg','26-28 Jun',71,false],['British GP','Silverstone','3-5 Jul',52,true],['Belgian GP','Spa','17-19 Jul',44,false],['Hungarian GP','Budapest','24-26 Jul',70,false],['Dutch GP','Zandvoort','21-23 Aug',72,true],['Italian GP','Monza','4-6 Sep',53,false],['Spanish GP - Madrid','Madrid','11-13 Sep',57,false],['Azerbaijan GP','Baku','24-26 Sep',51,false],['Singapore GP','Singapore','9-11 Oct',62,true],['United States GP','Austin','23-25 Oct',56,false],['Mexico City GP','MexicoCity','30 Oct-1 Nov',71,false],['Sao Paulo GP','SaoPaulo','6-8 Nov',71,false],['Las Vegas GP','LasVegas','19-21 Nov',50,false],['Qatar GP','Lusail','27-29 Nov',57,false],['Abu Dhabi GP','YasMarina','4-6 Dec',58,false]
    ];

    const f2CalendarRaw = [
        ['Melbourne','Melbourne','6-8 Mar',33],['Miami','Miami','1-3 May',35],['Montreal','Montreal','22-24 May',40],['Monaco','Monaco','4-7 Jun',42],['Barcelona','Barcelona','12-14 Jun',37],['Spielberg','Spielberg','26-28 Jun',40],['Silverstone','Silverstone','3-5 Jul',29],['Spa-Francorchamps','Spa','17-19 Jul',25],['Budapest','Budapest','24-26 Jul',37],['Monza','Monza','4-6 Sep',30],['Madrid','Madrid','11-13 Sep',34],['Baku - Supersized','Baku','24-26 Sep',29],['Lusail','Lusail','27-29 Nov',32],['Yas Marina','YasMarina','4-6 Dec',33]
    ];

    const f1Calendar = f1CalendarRaw.map((item,index) => ({round:index+1,name:item[0],venue:item[1],date:item[2],laps:item[3],sprint:item[4],special:false,path:tracks[item[1]]}));
    const f2Calendar = f2CalendarRaw.map((item,index) => ({round:index+1,name:item[0],venue:item[1],date:item[2],laps:item[3],sprint:true,special:item[1]==='Baku',path:tracks[item[1]]}));

    const driverSkills = [
        ['pace','Passo','Velocita pura sul giro'],['qualifying','Qualifica','Performance sul giro secco'],['racecraft','Racecraft','Sorpassi e difesa'],['tyres','Gestione gomme','Degrado e stint lunghi'],['feedback','Feedback','Qualita indicazioni tecniche'],['wet','Bagnato','Grip in condizioni variabili'],['focus','Concentrazione','Riduce errori e incidenti'],['adaptability','Adattabilita','Reazione a pista e setup']
    ];
    const engineerSkills = [
        ['strategy','Strategia','Pit window, undercut e Safety Car'],['tyres','Lettura gomme','Degrado e previsione stint'],['communication','Comunicazione','Radio chiara sotto pressione'],['setup','Setup','Bilanciamento e direzione tecnica'],['data','Analisi dati','Telemetria e pattern'],['pressure','Pressione','Decisioni nei momenti critici'],['weather','Meteo','Lettura transizioni e crossover'],['leadership','Leadership','Fiducia pilota e squadra']
    ];

    const history = {
        rookie:{bonus:0,rep:18,label:'Rookie'},
        f3top10:{bonus:2,rep:26,label:'F3 Top 10'},
        f3podiums:{bonus:4,rep:34,label:'F3 con podi'},
        f3champion:{bonus:7,rep:45,label:'Campione F3'},
        f2top10:{bonus:5,rep:40,label:'F2 Top 10'},
        f2winner:{bonus:8,rep:53,label:'Vincitore F2'},
        f2champion:{bonus:12,rep:70,label:'Campione F2'}
    };

    const ratings = {
        'George Russell':94,'Kimi Antonelli':90,'Charles Leclerc':94,'Lewis Hamilton':91,'Lando Norris':95,'Oscar Piastri':94,'Max Verstappen':97,'Isack Hadjar':89,'Fernando Alonso':92,'Lance Stroll':84,'Carlos Sainz':90,'Alexander Albon':89,'Nico Hulkenberg':87,'Gabriel Bortoleto':87,'Liam Lawson':86,'Arvid Lindblad':82,'Esteban Ocon':86,'Oliver Bearman':88,'Pierre Gasly':87,'Franco Colapinto':84,'Sergio Perez':87,'Valtteri Bottas':88,
        'Rafael Camara':91,'Joshua Duerksen':87,'Ritomo Miyata':86,'Colton Herta':90,'Noel Leon':85,'Nikola Tsolov':90,'Dino Beganovic':89,'Roman Bilinski':85,'Gabriele Mini':89,'Oliver Goethe':88,'Sebastian Montoya':86,'Mari Boya':88,'Martinius Stenshorne':88,'Alexander Dunne':90,'Kush Maini':87,'Tasanapol Inthraphuvasak':84,'Emerson Fittipaldi':84,'Cian Shields':82,'Hiyu Yamakoshi':82,'Rafael Villagomez':83,'Laurens van Hoepen':85,'John Bennett':82
    };

    return {
        tracks,
        teams:{F1:f1Teams,F2:f2Teams},
        calendars:{F1:f1Calendar,F2:f2Calendar},
        skills:{driver:driverSkills,engineer:engineerSkills},
        history,
        ratings,
        engineers:['Maya Keller','Tomas Ricci','Elena Moreau','Jack Mercer','Noah Stein','Aya Nakamura','Sofia Marin','Luca Bernardi','Claire Evans','Jonas Falk','Marta Silva']
    };
})();
