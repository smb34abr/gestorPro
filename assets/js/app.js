
"use strict";

document.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.querySelector("#sidebar");
  const menuToggle = document.querySelector("#menuToggle");
  const taskSearch = document.querySelector("#taskSearch");
  const taskTable = document.querySelector("#taskTable");
  const emptyMessage = document.querySelector("#emptyMessage");
  const navLinks = document.querySelectorAll(".nav-link");

  // Abrir y cerrar el menú en pantallas pequeñas.
  menuToggle.addEventListener("click", () => {
    const isOpen = sidebar.classList.toggle("open");
    menuToggle.setAttribute("aria-expanded", String(isOpen));
  });

  // Cerrar el menú al seleccionar una sección.
  navLinks.forEach((link) => {
    link.addEventListener("click", () => {
      navLinks.forEach((item) => item.classList.remove("active"));
      link.classList.add("active");

      sidebar.classList.remove("open");
      menuToggle.setAttribute("aria-expanded", "false");
    });
  });

  // Filtrar las tareas por texto.
  taskSearch.addEventListener("input", () => {
    const searchTerm = taskSearch.value
      .trim()
      .toLocaleLowerCase("es-MX");

    const rows = taskTable.querySelectorAll("tr");
    let visibleRows = 0;

    rows.forEach((row) => {
      const content = row.textContent.toLocaleLowerCase("es-MX");
      const matches = content.includes(searchTerm);

      row.hidden = !matches;

      if (matches) {
        visibleRows++;
      }
    });

    emptyMessage.hidden = visibleRows > 0;
  });

  // Mostrar una notificación sencilla al usar el botón.
  const viewProjects = document.querySelector("#viewProjects");

  viewProjects.addEventListener("click", () => {
    document.querySelector("#proyectos").scrollIntoView({
      behavior: "smooth",
      block: "start"
    });
  });

  // Año actual y estado inicial.
  console.info("GestorPro v0.1.0: interfaz cargada correctamente.");
});
