$(function () {

  function updateXlNums() {
    $(".update-xl-num").each(function () {
      let value = Math.floor(Math.random() * (15000 - 3000 + 1)) + 3000;
      $(this).text(value.toLocaleString());
    });
  }

  function updateLargeNums() {
    $(".update-large-num").each(function () {
      $(this).text(Math.floor(Math.random() * 1000) + 100);
    });
  }


  function updateMediumNums() {
    $(".update-medium-num").each(function () {
      $(this).text("+" + Math.floor(Math.random() * 500));
    });
  }

  function updateSmallNums() {
    $(".update-small-num").each(function () {
      let iconHtml = $(this).find("i").prop("outerHTML");
      $(this).html(iconHtml + " " + (Math.random() * 50).toFixed(1) + "%");
    });
  }

  function updateAllNumbers() {
    updateXlNums();
    updateLargeNums();
    updateMediumNums();
    updateSmallNums();
  }

  // Initial run
  updateAllNumbers();

  // Update every 2 seconds
  setInterval(updateAllNumbers, 2000);

});