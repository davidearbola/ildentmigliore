<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="container text-center py-5">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">
        
        <img 
          src="@/assets/images/logo-IDM.png" 
          alt="Logo Il Dentista Migliore" 
          class="img-fluid mb-4"
          style="max-width: 250px;"
        >

        <h1 class="h2 fw-bold mb-3">
          Una Rivoluzione per il Tuo Sorriso sta Arrivando!
        </h1>

        <p class="lead text-muted mb-4">
          Stiamo mettendo a punto gli ultimi dettagli per offrirti la piattaforma definitiva dove i tuoi preventivi dentistici trovano le migliori controproposte. 
          <br><br>
          A breve, potrai caricare il tuo preventivo in un attimo e lasciare che i migliori studi medici della tua zona ti offrano il prezzo migliore. Risparmiare non è mai stato così semplice!
        </p>
        
        <RouterLink :to="{ name: 'home' }" class="btn btn-primary btn-lg">
          Torna alla Home
        </RouterLink>

      </div>
    </div>
  </div>
</template>
