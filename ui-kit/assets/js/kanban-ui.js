$(function () {
  $(".kanban-items").sortable({
    connectWith: ".kanban-items",
    placeholder: "kanban-item-placeholder",
    receive: function (event, ui) {
      console.log("Item moved to a new column");
    }
  }).disableSelection();
});