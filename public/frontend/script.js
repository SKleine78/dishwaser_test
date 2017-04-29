var page = {
  config: {
      url: '/api/v1/'
  },
  initDeviceTypes: function() {
      var self = this;

      // load device type info
      $.getJSON(this.config.url+'info', function(data) {
          $('#model-create-device-type').html('');
          $.each(data, function(key, element) {
              $('#model-create-device-type').append('<option value="'+ element.type +'">'+ element.type + ': '+ element.description + '</option>');
          });
      });

      // submit new device
      $('#modal-create-device-form').submit(function(event) {
          event.preventDefault();
          $.post(self.config.url+'devices', {type: $('#model-create-device-type').val(), description: $('#model-create-device-description').val() }, function(data) {
              self.initDevices();
              $('#modal-create-device').modal('hide');
              $('#modal-create-device-description').val('');
          });
      });
  },
  initDevices: function() {
      var self = this;

      // load existing devices
      $.getJSON(this.config.url+'devices', function(data) {
          $('#device-list').html('');
          $.each(data, function(key, element) {
              $('#device-list').append('<li class="list-group-item" data-hash="'+ element.hash +'"><span class="device-name">'+ element.type + ' - ' + element.hash + ': '+ element.description + '</span><span class="glyphicon glyphicon-minus pull-right device-remove"></span></li>');
          });

          // add click handler to show modal
          $('#device-list li span.device-name').click(function(event) {
              $.get(self.config.url+'devices/'+$(this).parent().data('hash'), function(data) {
                  self.updateDishwasherDeviceModal(data);
                  $('#modal-show-dishwasher').modal();
              });
          });

          // add click handler to remove device
          $('#device-list li span.device-remove').click(function(event) {
              $.ajax(self.config.url+'devices/'+$(this).parent().data('hash'), {method: 'delete'})
              .done(function(data) {
                  self.initDevices();
              });
          });
      });
  },
  updateDishwasherDeviceModal: function(data) {
      $('.btn-modal-show-dishwasher-status').removeClass('active');
      $('.btn-modal-show-dishwasher-door').removeClass('active');
      $('#modal-show-dishwasher-title-hash').html(data.hash);
      $('#modal-show-dishwasher .modal-body').data('hash', data.hash);
      $('#modal-show-dishwasher-refresh').data('hash', data.hash);
      $('#modal-show-dishwasher button[data-value="'+data.status+'"]').addClass('active');
      $('#modal-show-dishwasher button[data-value="'+data.door+'"]').addClass('active');
      $('#modal-show-dishwasher-program').html(data.currentProgram);
      $('#modal-show-dishwasher-remaining').html(data.currentProgramRemaining);
  },
  initDishwasherModal: function() {
      var self = this;
      $('.btn-modal-show-dishwasher-door').click(function(event) {
          var $this = $(this);
          if (!$this.hasClass('active')) {
              var hash = $this.parent().parent().parent().data('hash');
              $('.btn-modal-show-dishwasher-door').removeClass('active');
              $.ajax(self.config.url+'devices/'+hash, {method: 'put', data: {door: $this.data('value')}})
                  .fail(function(data){
                      self.showDeviceModalAlert(data.responseText);
                  })
                  .done(function(data) {
                      self.updateDishwasherDeviceModal(data);
                  });
          }
      });
      $('.btn-modal-show-dishwasher-status').click(function(event) {
          var $this = $(this);
          if (!$this.hasClass('active')) {
              var hash = $this.parent().parent().parent().data('hash');
              $('.btn-modal-show-dishwasher-status').removeClass('active');
              $.ajax(self.config.url+'devices/'+hash, {method: 'put', data: {status: $this.data('value')}})
                  .fail(function(data){
                      self.showDeviceModalAlert(data.responseText);
                  })
                  .done(function(data) {
                      self.updateDishwasherDeviceModal(data);
                  });
          }
      });
      $('#modal-show-dishwasher-refresh').click(function() {
          $.get(self.config.url+'devices/'+$(this).data('hash'), function(data) {
              self.updateDishwasherDeviceModal(data);
          });
      });
  },
  showDeviceModalAlert: function(text) {
      $('#modal-show-dishwasher-alert-text').html(text);
      $('#modal-show-dishwasher-alert').removeClass('hidden');
      setTimeout(function() {
          $('#modal-show-dishwasher-alert').addClass('hidden');
      }, 3000);
  },
  init: function() {
        // init existing devices
        this.initDevices();
        // init device types
        this.initDeviceTypes();
        // init dishwasher modal view observer
        this.initDishwasherModal();
  }

};

$(document).ready(function() {
    var myPage = page.init();
});
