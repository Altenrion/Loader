class Helper_Sorter
{
    public static function listData($models, $valueField, $textField, $groupField='')
      {
          $listData=array();
          if (!is_array($models) || empty($models))
              return $listData;
          if (empty($textField) || $textField == '*')
              $textField = null;
          if($groupField==='')
          {
              foreach($models as $model)
              {
                  $value=self::value($model,$valueField);
                  $text=is_null($textField) ? $model : self::value($model,$textField);
                  $listData[$value]=$text;
              }
          }
          else
          {
              foreach($models as $model)
              {
                  $group=self::value($model,$groupField);
                  $value=self::value($model,$valueField);
                  $text=is_null($textField) ? $model : self::value($model,$textField);
                  if($group===null)
                      $listData[$value]=$text;
                  else
                      $listData[$group][$value]=$text;
              }
          }
          return $listData;
      }
  
      public static function value($model,$attribute,$defaultValue=null)
      {
          if(is_scalar($attribute) || $attribute===null)
              foreach(explode('.',$attribute) as $name)
              {
                  if(is_object($model) && isset($model->$name))
                      $model=$model->$name;
                  elseif(is_array($model) && isset($model[$name]))
                      $model=$model[$name];
                  else
                      return $defaultValue;
              }
          else
              return call_user_func($attribute,$model);
  
          return $model;
      }
}
