    <!-- Fin del Contenido Principal -->
           </div> <!-- /content-container -->
        </div> <!-- /page-content-wrapper -->
    </div> <!-- /wrapper -->

<footer class="footer mt-auto py-3 bg-white text-center border-top">
  <div class="container">
    <span class="text-muted small">© 2026 Personería Municipal de El Retiro</span>
  </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Script para el Sidebar -->
<script>
    document.getElementById("menu-toggle").addEventListener("click", function(e) {
        e.preventDefault();
        document.getElementById("wrapper").classList.toggle("toggled");
        
        let sidebar = document.getElementById("sidebar-wrapper");
        let content = document.getElementById("page-content-wrapper");
        
        // Lógica simple de toggle margin
        if (sidebar.style.marginLeft === "0px" || sidebar.style.marginLeft === "") {
             if(window.innerWidth > 768) {
                sidebar.style.marginLeft = "-250px";
                content.style.marginLeft = "0";
             } else {
                sidebar.style.marginLeft = "0"; // Mostrar en móvil
             }
        } else {
             if(window.innerWidth > 768) {
                sidebar.style.marginLeft = "0";
                content.style.marginLeft = "250px";
             } else {
                sidebar.style.marginLeft = "-250px"; // Ocultar en móvil
             }
        }
    });
</script>

</body>
</html>
