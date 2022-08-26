$(function()
{

    // 访问服务器 Pi payment接口 
    // http://localhost:9000/api.php?s=PaymentPi/approval
    console.log("create axios:");
    console.log(window.location.origin);
    var instance = axios.create({
        //baseURL: 'https://pime.app',
        baseURL: window.location.origin,
        timeout: 20000
      });


    function paymentApi_cancel (paymentId, metadata) {

      return new Promise((resolve, reject) => {
        instance.post( '/api.php?s=PaymentPi/cancel',
        {
          ...metadata, 
          ...{
            paymentId : paymentId
          }
        })
        .then(function (response) {
          console.log(response)
        })
        .catch(function (error) {
          console.log(error)
        })
      })

    }

    function paymentApi_approval (paymentId, metadata) {

      var that = this;
      return instance.post( '/api.php?s=PaymentPi/approval',
      {
          ...metadata, 
          ...{
            paymentId : paymentId
          }
      })
      .then(function (response) {
        console.log(response)
        console.log('approvalSendPi ok')
      })
      .catch(function (error) {
        console.log(error)
        console.log('approvalSendPi error')
        paymentApi_cancel(paymentId, metadata);
      })
    }

    function paymentApi_complete (paymentId, txid, metadata) {
      return instance.post( '/api.php?s=PaymentPi/complete',
      {
        ...metadata, 
        ...{
          paymentId : paymentId,
          txid : txid
        }
      })
      .then(function (response) {
        console.log(response)
      })
      .catch(function (error) {
        console.log(error)
      })
    }

    function paymentApi_incomplete (payment) {
      return instance.post( '/api.php?s=PaymentPi/incomplete',
      {
        payment
      })
      .then(function (response) {
        console.log(response)
      })
      .catch(function (error) {
        console.log(error)
      })
    }


    // 支付窗口
    var $pay_popup = $('#order-pay-popup');

    // 支付窗口参数初始化
    function PayPopupParamsInit(ids, payment_id)
    {
        // 数组则转成字符串
        if(IsArray(ids))
        {
            ids = ids.join(',');
        }
        $('form.pay-form input[name=ids]').val(ids);
        if((payment_id || null) != null && $('.payment-items-'+payment_id).length > 0)
        {
            $('form.pay-form input[name=payment_id]').val(payment_id);
            $('.payment-items-'+payment_id).addClass('selected').siblings('li').removeClass('selected');
        } else {
            $('form.pay-form input[name=payment_id]').val(0);
            $('ul.payment-list li.selected').removeClass('selected');
        }
    }
    // 支付操作
    $('.submit-pay').on('click', function()
    {
        // comment by jiakuant 
        // here , raise a pay
        // PayPopupParamsInit($(this).data('id'), $(this).data('payment-id'));
        // $pay_popup.modal();

        console.log('begin payment');
        console.log($(this).data('id'));
        console.log($(this).data('payment-id'));
        console.log(window)
        console.log(window.Pi)

        // PiSDK.sendPi({
        //   pid: projectId, 
        //   uid: this.auth?.user?.uid,
        //   amount: 3.14,
        //   memo: "for test"
        // })


        Pi.createPayment({
            // Amount of π to be paid:
            amount: 3.14,
            // An explanation of the payment - will be shown to the user:
            memo: "test", // e.g: "Digital kitten #1234",
            // An arbitrary developer-provided metadata object - for your own usage:
            metadata: { id: $(this).data('id') } // e.g: { kittenId: 1234 }
            // to_address: to_address,
          }, {
            // Callbacks you need to implement - read more about those in the detailed docs linked below:
            onReadyForServerApproval: function(paymentId) {
              console.log('onReadyForServerApproval:' + paymentId);
              paymentApi_approval(paymentId, { id: $(this).data('id') })
            },
            onReadyForServerCompletion: function(paymentId, txid) {
              console.log('onReadyForServerCompletion:' + paymentId + ',' + txid);
              paymentApi_complete(paymentId, txid, metadata);

              // payment process compelted
            },
            onCancel: function(paymentId) {
              console.log('onCancel:' + paymentId);
              paymentApi_cancel(paymentId, metadata);
            },
            onError: function(error, payment) {
              console.log('onError:' + error + ',' + payment);
              if (payment) {
                console.log(payment);
              }
            },
        });


    });

    // 混合列表选择
    $('.business-item ul li').on('click', function()
    {
        if($(this).hasClass('selected'))
        {
            $('form.pay-form input[name='+$(this).parent().data('type')+'_id]').val(0);
            $(this).removeClass('selected');
        } else {
            $('form.pay-form input[name='+$(this).parent().data('type')+'_id]').val($(this).data('value'));
            $(this).addClass('selected').siblings('li').removeClass('selected');
        }
    });

    // 支付表单
    $('form.pay-form button[type=submit]').on('click', function()
    {
        var ids = $('form.pay-form input[name=ids]').val() || null;
        if(ids == null)
        {
            Prompt('订单id有误');
            return false;
        }
        var payment_id = $('form.pay-form input[name=payment_id]').val() || 0;
        if(payment_id == 0)
        {
            Prompt('请选择支付方式');
            return false;
        }
    });

    /**
     * 评价打分
     */
    $('ul.rating li').on('click', function()
    {
        $(this).parent().find('li i').removeClass('am-icon-star').addClass('am-icon-star-o');
        var index = $(this).index();
        var rating_msg = ['非常差', '差', '一般', '好', '非常好'];
        for(var i=0; i<=index; i++)
        {
            $(this).parent().find('li').eq(i).find('i').removeClass('am-icon-star-o').addClass('am-icon-star');
        }
        $(this).parent().find('li.tips-text').text(rating_msg[index]);
        $(this).parents('td').find('input.input-rating').val(index+1).trigger('blur');
        $(this).parent().removeClass('not-selected');
    });

    // 自动支付处理
    if($pay_popup.length > 0)
    {
        // 是否自动打开支付窗口
        if($pay_popup.data('is-auto') == 1)
        {
            $pay_popup.modal();
        }

        // 是否自动提交支付表单
        if($pay_popup.data('is-pay') == 1)
        {
            $pay_popup.find('button[type="submit"]').trigger('click');
        }
    }

    // 批量支付
    $('.batch-pay-submit').on('click', function()
    {
        // 是否有选择的数据
        var values = FromTableCheckedValues('order_form_checkbox_value', '.am-table-scrollable-horizontal');
        if(values.length <= 0)
        {
            Prompt('请先选中数据');
            return false;
        }

        // 支付url支付地址
        var url = $(this).data('url') || null;
        if(url == null)
        {
            Prompt('支付url地址有误');
            return false;
        }

        // 获取第一个订单支付方式
        var payment_id = $('#data-list-'+values[0]).find('.submit-pay').data('payment-id') || null;

        // 支付弹窗
        PayPopupParamsInit(values, payment_id);
        $pay_popup.modal();
    });
});


$(function()
{
    console.log("Pi.init begin");

    console.log(window)

    console.log(navigator.userAgent)

    console.log(window.location.origin);

    if (window.location.origin == 'https://localhost:4430') {
      console.log("Pi.init with sandbox to true")
      Pi.init({ version: "2.0", sandbox: true});
    }else{
      console.log("Pi.init with sandbox to false")
      Pi.init({ version: "2.0"});
    }

    //Pi.init({ version: "2.0"});


    console.log("Pi.init end");
    console.log(Pi);
    console.log(Pi.authenticate);


    function onIncompletePaymentFound(payment) {
        console.log("call onIncompletePaymentFound");
    };


    // 直接获取授权
    console.log("getPiUserInfo begin");
    console.log(Pi);

    console.log(Pi.authenticate);
    console.log(window)

    Pi.authenticate(['username', 'payments'], onIncompletePaymentFound).then(function(auth) {
        console.log(`Hi there! You're ready to make payments!`);
        console.log(auth?.user?.username);
        console.log(auth?.user?.uid);
        console.log(auth?.accessToken);

    }).catch(function(error) {
        console.log(error);
    });


});
